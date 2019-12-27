<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        @media only screen and (max-width: 620px) {
            table[class=body] h1 {
                font-size: 28px !important;
                margin-bottom: 10px !important;
            }
            table[class=body] p,
            table[class=body] ul,
            table[class=body] ol,
            table[class=body] td,
            table[class=body] span,
            table[class=body] a {
                font-size: 16px !important;
            }
            table[class=body] .wrapper,
            table[class=body] .article {
                padding: 10px !important;
            }
            table[class=body] .content {
                padding: 0 !important;
            }
            table[class=body] .container {
                padding: 0 !important;
                width: 100% !important;
            }
            table[class=body] .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }
            table[class=body] .btn table {
                width: 100% !important;
            }
            table[class=body] .btn a {
                width: 100% !important;
            }
            table[class=body] .img-responsive {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
            }
        }
        @media all {
            table[class=main] {
				-webkit-box-shadow: -18.656px 24.758px 114px 0 rgba(0,0,0,.09);
				-moz-box-shadow: -18.656px 24.758px 114px 0 rgba(0,0,0,.09);
				-o-box-shadow: -18.656px 24.758px 114px 0 rgba(0,0,0,.09);
				box-shadow: -18.656px 24.758px 114px 0 rgba(0,0,0,.09);
			}
			
            .ExternalClass {
                width: 100%;
            }
            .ExternalClass,
            .ExternalClass p,
            .ExternalClass span,
            .ExternalClass font,
            .ExternalClass td,
            .ExternalClass div {
                line-height: 100%;
            }
            .apple-link a {
                color: inherit !important;
                font-family: inherit !important;
                font-size: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
                text-decoration: none !important;
            }
            .btn-primary table td:hover {
                background-color: #5f6e77 !important;
            }
            .btn-primary a:hover {
                background-color: #5f6e77 !important;
                border-color: #5f6e77 !important;
            }
        }
		
		.ga_appointment_content,
		.ga_appointment_content p {
			color: #666;
		}
		
		.ga_add_to_calendar_links {
			color: #b4c0c6;
			text-transform: uppercase;
			font-size: 10px;
			display: inline-block;
			padding-left: 4px;			
		}
	
		.ga_add_to_calendar_links a {
			text-decoration: none;
			color: #b4c0c6;
			text-transform: uppercase;
			font-size: 10px;
			padding: 0px 3px;
			letter-spacing: 1px;
		}		
	
		.ga_add_to_calendar_links a:hover {
			color: #1dd59a;
		}			
		
    </style>
</head>

<body class="" style="background-color: #eff0f3; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
    <table border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: #eff0f3;">
        <tr>
            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
            <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; Margin: 0 auto; max-width: 580px; padding: 60px 10px; width: 580px;">
                <div class="content" style="box-sizing: border-box; display: block; Margin: 0 auto; max-width: 580px;">
				    <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background: #ffffff; border-radius: 3px; box-shadow: -18.656px 24.758px 114px 0 rgba(0,0,0,.09);">
							<?php 
								$notifications = get_option( 'ga_appointments_notifications' );
								if( isset($notifications['logo']) && !empty($notifications['logo']) ) {
									echo '<div style="margin-bottom: 10px;"><img src="'. esc_url($notifications['logo']) .'" style="max-width: 70%; margin: 0 auto; display: block;"></div>';
								}
							?>
                        <tr>
                            <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                                <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                                    <tr>
                                        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
											<div><h3 style="margin: 0; font-size: 14px; font-weight: 600; letter-spacing: 1px; text-align: center; text-transform: uppercase;">%appointment_heading_content%</h3></div>
											<div class="ga_appointment_content">%appointment_body_content%</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <div class="footer" style="clear: both; Margin-top: 10px; text-align: center; width: 100%;">
                        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                            <tr>
                                <td class="content-block powered-by" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; font-size: 12px; color: #999999; text-align: center;"><?php echo wp_kses_post( wptexturize( '<a target="_blank" href="'.site_url().'" style="color: #999999; font-size: 12px; text-align: center; text-decoration: none;">' . get_bloginfo() . '</a>' ) ); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
        </tr>
    </table>
</body>
</html>