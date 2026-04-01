<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; padding: 0; background-color: #f6f6f6; font-family: Helvetica, Arial, sans-serif; }
        .container { max-width: 650px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 4px; border: 1px solid #e0e0e0; }
        .content { font-size: 16px; line-height: 1.6; color: #333333; margin-bottom: 30px; }
        .signature { margin-top: 30px; font-size: 14px; color: #555555; line-height: 1.5; }
        .signature a { color: #007bff; text-decoration: none; }
        .footer-logos { width: 100%; border-top: 2px dotted #cccccc; margin-top: 25px; padding-top: 15px; }
        .footer-logos td { vertical-align: middle; }
    </style>
</head>
<body>
    <div style="background-color: #f6f6f6; padding: 20px 0;">
        <div class="container">
            
            <div class="content">
                {!! nl2br(e($bodyText)) !!}
            </div>

            <div class="signature">
                Kind regards,<br>
                <strong>Jelly</strong><br><br>
                --<br>
                <strong>Jhelly Goode</strong><br>
                Tel: <a href="tel:1800244386">1800 BIGFUN (244-386)</a><br>
                Web: <a href="http://www.bigfun.com.au">bigfun.com.au</a> | 
                     <a href="http://www.starhire.com.au">starhire.com.au</a> | 
                     <a href="http://www.theinflatablefactory.com.au">theinflatablefactory.com.au</a><br>
                Facebook: <a href="http://www.facebook.com/bigfunattractions/">Big Fun Attractions</a><br>
                Youtube: <a href="http://www.youtube.com/user/bigfunattractions">Big Fun Channel</a>
            </div>

            <table class="footer-logos" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="left" width="33%">
                        <img src="cid:img_inflatable" style="max-height:60px; width:auto; display:block;" alt="The Inflatable Factory">
                    </td>
                    <td align="center" width="34%">
                        <img src="cid:img_bigfun" style="max-height:60px; width:auto; display:block;" alt="Big Fun">
                    </td>
                    <td align="right" width="33%">
                        <img src="cid:img_starhire" style="max-height:60px; width:auto; display:block;" alt="Starhire">
                    </td>
                </tr>
            </table>

        </div>
        <div style="text-align:center; font-size:12px; color:#999; margin-top:20px;">
            &copy; {{ date("Y") }} Big Fun. All rights reserved.
        </div>
    </div>
</body>
</html>
