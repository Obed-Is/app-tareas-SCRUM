<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function envio_codigo($email)
{
    unset($_SESSION["erroremail"]);
    $codigo_recuperacion = $_SESSION["_codigo"];
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        ///// SE USA ESTE CORREO PORQUE NO QUIERO HACER OTRO SOLO PARA ESTO YA QUE NECESITA LA VERIFICACION EN 2 PASOS ///////
        ///Y TENER QUE GENERAR UNA CLAVE AL CORREO PARA QUE DEJE SER USADA ASI Q MEJOR USO ESTA QUE YA TENIA PARA REUTILIZAR ///////
        $mail->Username = 'oroverde325@gmail.com';
        $mail->Password = 'hvor mwcv ijwx edwj';         
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port = 465;

        $mail->setFrom('oroverde325@gmail.com', 'TaskApp');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Codigo de verificacion';

        $mailContent = "
                        <div style='font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;'>
                            <div style='max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px;'>
                                <h2 style='color: #333;'>Codigo de verificacion</h2>
                                <p>Recibimos una solicitud para cambiar la contraseña de tu cuenta.  
                                Tu código de verificación es:</p>
                                <div style='font-size: 28px; font-weight: bold; letter-spacing: 8px; color: #2F80ED; margin: 20px 0;'>
                                    {$codigo_recuperacion}
                                </div>
                                <p>Este codigo tiene una duracion de 2 minutos</p>
                                <p style='font-size: 12px; color: #777;'>Si no solicitaste este código, puedes ignorar este mensaje.</p>
                            </div>
                        </div>
                        ";

        $mail->Body = $mailContent;

        $mail->send();
        echo "Correo enviado correctamente.";
    } catch (Exception $e) {
        $_SESSION["erroremail"] = "Ocurrio un error al enviar el codigo, intente de nuevo o mas tarde";
    }

}