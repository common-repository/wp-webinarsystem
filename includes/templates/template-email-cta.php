<?php
// https://github.com/leemunroe/responsive-html-email-template/blob/master/email-inlined.html

// we should get params from our input data
$params = isset($params)
    ? $params : (object) [];
?>

<table border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; box-sizing: border-box;">
    <tbody>
    <tr>
        <td align="left" style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding-bottom: 15px;">
            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto;">
                <tbody>
                <tr>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; background-color: #3498db; border-radius: 5px; text-align: center;">
                        <a
                            href="<?php echo esc_url($params->url) ?>"
                            target="_blank"
                            style="display: inline-block; color: <?php echo esc_attr($params->button_text_color) ?>; background-color: <?php echo esc_attr($params->button_background_color) ?> !important; border: solid 1px #3498db; border-radius: 5px; box-sizing: border-box; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; margin: 0; padding: 10px 15px; border-color: <?php echo esc_attr($params->button_background_color) ?> !important;"
                        >
                            <?php echo esc_html($params->text) ?>
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>