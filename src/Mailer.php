<?php
namespace izi\mailer;

use PHPMailer\PHPMailer\PHPMailer;
 
class Mailer extends \PHPMailer\PHPMailer\PHPMailer
{
    
    public function __construct(){
        $this->isSMTP();
        $this->CharSet = "UTF-8";
        $this->SMTPDebug = 0;
        
        $this->Debugoutput = 'html';
        
        $this->SMTPAuth = true;
        $this->isHTML(true);
        
        
        /**
         * Get smtp config 
         */
        
        $emails = Yii::$app->db->getConfigs('EMAILS',false);

        $smtp = $this->getDefautSmtp();
        $sms = [];
        if(isset($emails['listItem']) && !empty($emails['listItem'])){
            foreach ($emails['listItem'] as $v){
                if( isset($v['is_default']) && $v['is_default'] == 1 && isset($v['is_active']) && $v['is_active'] == 1){
                    $sms = $v;
                    break;
                }
            }
            if(empty($sms)){
                $sms = $emails['listItem'][0] ;
            }
            if(!empty($sms))  $smtp = $sms;
        }

        //$this->From =   isset(Yii::$app->contact['email']) ? Yii::$app->contact['email'] : 'no-reply@'. DOMAIN;
        
        $this->From = $smtp['email'];
        
        $this->FromName = isset(Yii::$app->contact['short_name']) ? Yii::$app->contact['short_name'] : DOMAIN;
        
        $this->Host = $smtp['host'];
        
         
        // use
        // $mail->Host = gethostbyname('smtp.gmail.com');
        // if your network does not support SMTP over IPv6
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $this->Port = $smtp['port'];
        //Set the encryption system to use - ssl (deprecated) or tls
        $this->SMTPSecure = $smtp['smtpsecure'];
        //Whether to use SMTP authentication
        
        //Username to use for SMTP authentication - use full email address for gmail
        $this->Username = $smtp['email'];
        //Password to use for SMTP authentication
        $this->Password = dString($smtp['password']);
        
        $this->smtpConnect([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        parent::__construct();
    }
    
    
    public function getDefautSmtp(){
        return [
            'host' => 'smtp.yandex.ru',  // e.g. smtp.mandrillapp.com or smtp.gmail.com
            'email' => 'no-reply@iziweb.vn',
            'password' => 'eTM2cGVUTTJjR1EyYXprM1VVdFI=',
            'port' => 587, // Port 25 is a very common port too
            'smtpsecure' => 'tls', // It is often used, check your provider or mail server specs
        ];
    }
    
    
    public function sentEmail($params){
        return $this->sendEmail($params);
    }
    
    public function sendEmail($o){
        /**
         * 
         * @var Ambiguous $subject
         */
        
        $mailer = new Mailer; 
        
        $subject = isset($o['subject']) ? $o['subject'] : '';
        $body = isset($o['body']) ? $o['body'] : '';
        $messageBody = isset($o['messageBody']) ? $o['messageBody'] : $body;
        
        $from = isset($o['from']) ? $o['from'] : $this->From;
        $from = isset($smtp['from_email']) && $smtp['from_email'] != "" ? $smtp['from_email'] : (isset($smtp['email']) ? $smtp['email'] : $from); // replace with your own
        
        $fromEmail = isset($smtp['fromEmail']) && $smtp['fromEmail'] != "" ? $smtp['fromEmail'] : (isset($smtp['email']) ? $smtp['email'] : $from); // replace with your own
        
        
        $fromName = isset($o['fromName']) ? $o['fromName'] : (isset($smtp['from_name']) ? $smtp['from_name'] : $fromEmail); // replace with your own
        $replyTo = isset($o['replyTo']) ? $o['replyTo'] : ''; // replace with your own
        $replyToName = isset($o['replyToName']) ? $o['replyToName'] : ''; // replace with your own
        $templete = isset($o['templete']) ? $o['templete'] : [];
        $to = isset($o['to']) ? $o['to'] : '';
        $toName = isset($o['toName']) ? $o['toName'] : '';
        //
        
        $setFrom = $fromEmail != $fromName ? [$fromEmail => $fromName] : $fromEmail;               
        
        if (is_array($replyTo)){
            $email = array_keys($replyTo)[0];
            if(filter_var($email, FILTER_VALIDATE_EMAIL)){
                $mailer->addReplyTo($email,array_keys($var)[1]);
            }
            
        }elseif(filter_var($replyTo, FILTER_VALIDATE_EMAIL) && $this->From != $replyTo){ 
            $mailer->addReplyTo($replyTo,$replyToName);
        }         
        
        $mailer->Subject = $subject;
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        $mailer->msgHTML($messageBody);
        
        $toEmails = !is_array($to) ? explode(',', str_replace(';', ',', $to)) : $to;
        
        $cc = isset($o['cc']) ? $o['cc'] : '';
        
        $ccEmails = !is_array($cc) ? explode(',', str_replace(';', ',', $cc)) : $cc;
        
        $bcc = isset($o['bcc']) ? $o['bcc'] : '';
        
        $bccEmails = !is_array($bcc) ? explode(',', str_replace(';', ',', $bcc)) : $bcc;
        
        /**
         * $toEmails = [
         *      'email1@domain.com',
         *      'email2@domain.com',
         *      'email3@domain.com'
         * ]
         * 
         * $toEmails = [
         *      ['email1@domain.com'=>'Email 1'],
         *      ['email2@domain.com'=>'Email 2'],
         *      ['email3@domain.com'=>'Email 3']
         * ]
         */
        if(!empty($toEmails)){
            foreach ($toEmails as $k=>$var){
                if(is_array($var)){
                    $mailer->addAddress(
                        array_keys($var)[0],
                        array_values($var)[0]
                        );
                }else{
                    $mailer->addAddress($var,$toName);
                }
                unset($toEmails[$k]);
                break;
            }
        }
        
        /**
         * add Bcc email
         */
        $bccEmails = array_merge($bccEmails, $toEmails);
        
        if(!empty($bccEmails)){
            foreach ($bccEmails as $email){

                if(is_array($email)){
                    if(filter_var(array_keys($email)[0],FILTER_VALIDATE_EMAIL)){
                        $mailer->addCC(array_keys($email)[0],array_values($email)[0]);
                    }
                }elseif(filter_var($email,FILTER_VALIDATE_EMAIL)){
                    $mailer->addBCC($email);
                }
            }
        }
        
        /**
         * add cc email
         */        
        
        if(!empty($ccEmails)){
            foreach ($ccEmails as $email){
                if(is_array($email)){
                    if(filter_var(array_keys($email)[0],FILTER_VALIDATE_EMAIL)){
                        $mailer->addCC(array_keys($email)[0],array_values($email)[0]);
                    }
                }elseif(filter_var($email,FILTER_VALIDATE_EMAIL)){
                    $mailer->addCC($email);
                }
            }
        }
        
        return $mailer->send();
        
    }
}
