<?php declare(strict_types=1);

namespace Peanut\Schema\Fields;

class CaptchaField extends \Peanut\Schema\Fields
{
    public function fetch()
    {
        $name    = $this->getName();
        $id      = $this->getId();
        $sitekey = $this->schema->sitekey;
        $str     = <<<EOD
<script src='https://www.google.com/recaptcha/api.js'></script>
<script>
function recaptchaCallback() {
    $("#${name}").focus().blur();
    console.log('recaptchaCallback');
}
function recaptchaExpired() {
    window.setTimeout(function() {
        $("#${name}").focus().blur();
        console.log('recaptchaExpired');
    }, 1000);
}
</script>

<div class="form-group">
    <label class="control-label">recaptcha</label>
    <div class="col-sm-12">
        <div class="g-recaptcha" data-sitekey="${sitekey}" data-callback="recaptchaCallback" data-expired-callback="recaptchaExpired"></div>
    </div>
</div>
EOD;

        return $str;
    }
}
