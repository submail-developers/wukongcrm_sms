<?php
public function sendSms(){
    if($this->isPost()){
        $phoneNum = trim($_POST['phoneNum']);
        $message = trim($_POST['smsContent']);
        if($_POST['settime']){
            $send_time = strtotime(trim($_POST['sendtime']));
            if($send_time > time()){
                $sendtime = date('YmdHis',$send_time);
            }
        }
        $current_sms_num = getSmsNum();
        if(!F('sms')) alert('success',L('SEND_SMS_FAILED'),$_SERVER['HTTP_REFERER']);
        $phoneNum = str_replace(" ","",$phoneNum);
        $phone_array = explode(chr(10),$phoneNum);
//            var_dump($phone_array);die;

        if(sizeof($phone_array) > 0){
            //if(sizeof($phone_array) > $current_sms_num) alert('error','短信余额不足，请联系管理员，及时充值!',$_SERVER['HTTP_REFERER']);
        }
        $fail_array = array();
        $success_array = array();
        if($phoneNum && $message){
            if(strpos($message,'var(@name)',0) === false){
                $name_array=array();
                foreach($phone_array as $k=>$v){
                    if($v){
                        if(strlen($v) >11){
                            $name = substr($v,12);
                            $name_array[]   =   trim($name);
                        }
                        $phone = substr($v,0,11);
                        if(is_phone($phone)){
                            $success_array[] = $phone;
                        }else{
                            $fail_array[] = $v;
                        }
                    }
                }
                if(!empty($fail_array)){
                    $fail_message = L('PART_OF_NUMBER_SEND_FAILED').implode(',', $fail_array);
                }
                $result = sendGroupSMS(implode(',', $success_array),$message,'sign_name', $name_array);
                if($result == 1){
                    $m_sms_record=M('smsRecord');
                    $data['role_id'] = session('role_id');
                    $data['telephone'] = implode(',', $success_array);
                    $data['content'] = $message;
                    $data['sendtime'] = time();
                    $m_sms_record->add($data);
                    alert('success', L('SEND_SUCCESS_MAY_DELAY_BY_BAD_NETWORK').$fail_message,$_SERVER['HTTP_REFERER']);
                }else{
                    alert('error',L('SMS_NOTIFICATION_FAILS_CODE', array($result)),$_SERVER['HTTP_REFERER']);
                }
            }else{
                foreach($phone_array as $k=>$v){
                    $real_message = $message;
                    $name = '';
                    if($v){
                        $no = str_replace(" ","",$v);
                        $phone = substr($no,0,11);
                        if(is_phone($phone)){
                            if(strpos($v,',',0) === false){
                                $info_array = explode('，', $v);
                            }else{
                                $info_array = explode(',', $v);
                            }
                            $real_message = str_replace('{$name}',$info_array[1],$real_message);
                            $result =sendSMS($phone, $real_message, 'sign_name', $sendtime);
                            $m_sms_record=M('smsRecord');
                            $data['role_id']=session('role_id');
                            $data['telephone']=$phone;
                            $data['content']=$real_message;
                            $data['sendtime']=time();
                            $m_sms_record->add($data);

                            if($result<0 && $k==0){
                                alert('error', L('SMS_NOTIFICATION_FAILS_CODE', array($result)),$_SERVER['HTTP_REFERER']);
                            }
                        }else{
                            $fail_array[] = $v;
                        }
                    }
                }

                if(!empty($fail_array)){
                    $fail_message = L('PART_OF_NUMBER_SEND_FAILED').implode(',', $fail_array);
                }

                alert('success',L('SEND_SUCCESS_MAY_DELAY_BY_BAD_NETWORK').$fail_message,U('setting/sendsms'));

            }
        }else{
            alert('error',L('INCOMPLETE_INFORMATION'),$_SERVER['HTTP_REFERER']);
        }
    }else{
        $current_sms_num = getSmsNum();

        $model = trim($_GET['model']);
        if($model == 'customer'){
            $customer_ids = trim($_GET['customer_ids']);
            if($customer_ids){
                $contacts_ids = M('RContactsCustomer')->where('customer_id in (%s)', $customer_ids)->getField('contacts_id', true);
                $contacts_ids = implode(',', $contacts_ids);
                $contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
                $this->contacts = $contacts;
            }else{
                alert('error',L('SELECT_CUSTOMER_TO_SEND'),$_SERVER['HTTP_REFERER']);
            }
        }elseif($model == 'contacts'){
            $contacts_ids = trim($_GET['contacts_ids']);
            if(!$contacts_ids) alert('error',L('SELECT_CONTACTS_TO_SEND'),$_SERVER['HTTP_REFERER']);
            $contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
            $this->contacts = $contacts;
        }elseif($model == 'leads'){
            $d_v_leads = D('LeadsView');
            $leads_ids = trim($_GET['leads_ids']);
            $where['leads_id'] = array('in',$leads_ids);
            $customer_list = $d_v_leads->where($where)->select();
            $contacts = array();
            foreach ($customer_list as $k => $v) {
                $contacts[] = array('name'=>$v['contacts_name'], 'customer_name'=>$v['name'], 'telephone'=>trim($v['mobile']));
            }
            $this->contacts = $contacts;
        }
        $this->templateList = M('SmsTemplate')->order('order_id')->select();
        $this->alert = parseAlert();
        $this->current_sms_num = $current_sms_num;
        $this->display();
    }
}
