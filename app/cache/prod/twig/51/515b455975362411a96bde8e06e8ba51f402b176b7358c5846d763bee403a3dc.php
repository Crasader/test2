<?php

/* BBDurianBundle:Default/Demo/PaymentGateway:get.html.twig */
class __TwigTemplate_35a841186833c8c46ce4f6908856fa9f8c74915f6abd2d03af9de8e6a05b2434 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/PaymentGateway:get.html.twig", 1);
        $this->blocks = array(
            'content' => array($this, 'block_content'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "BBDurianBundle:Default/Demo:index.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = array())
    {
        // line 4
        echo "<div class=\"page-header\">
    <h1>Get PaymentGateway</h1>
    <code>GET /api/payment_gateway/{paymentGatewayId}</code>
</div>

<p class=\"lead\">回傳支付平台</p>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <label class=\"control-label col-md-2\"></label>
            <div class=\"col-md-7\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">/api/payment_gateway/</span>
                    <input data-request-param=\"paymentGatewayId\" type=\"text\" class=\"form-control\" placeholder=\"{paymentGatewayId}\">
                </div>
            </div>
            <p class=\"help-block col-md-offset-2 col-md-10\">網址變數{paymentGatewayId}輸入支付平台id，不輸入列出全部</p>
        </div>
        <div class=\"form-group\">
            <div class=\"col-md-offset-2 col-md-10\">
                <button data-request-routing=\"api_get_payment_gateway\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/PaymentGateway:get.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  31 => 4,  28 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo/PaymentGateway:get.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/PaymentGateway/get.html.twig");
    }
}
