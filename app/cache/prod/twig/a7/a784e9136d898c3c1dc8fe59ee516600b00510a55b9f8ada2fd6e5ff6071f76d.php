<?php

/* BBDurianBundle:Default/Demo/Withdraw:lock.html.twig */
class __TwigTemplate_905e7d5c3fb3f8f054b382c8d1c12022851823135fa28e7c0b1eb31eec18b989 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:lock.html.twig", 1);
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
    <h1>Withdraw Lock</h1>
    <code>PUT /api/cash/withdraw/{entryId}/lock</code>
</div>

<div class=\"row\">
    <div class=\"col-md-7\">
        <p class=\"lead\">鎖定出款資料</p>
    </div>
    ";
        // line 13
        $this->loadTemplate("BBDurianBundle:Default/Doc:sensitive_data.html.twig", "BBDurianBundle:Default/Demo/Withdraw:lock.html.twig", 13)->display($context);
        // line 14
        echo "</div>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <label class=\"control-label col-md-2\"></label>
            <div class=\"col-md-8\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">/api/cash/withdraw/</span>
                    <input data-request-param=\"entryId\" type=\"text\" class=\"form-control\" placeholder=\"{entryId}\">
                    <span class=\"input-group-addon\">/lock</span>
                </div>
            </div>
            <p class=\"help-block col-md-offset-2 col-md-10\">輸入網址變數{entryId}</p>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">operator</label>
            <div class=\"col-md-5\">
                <input name=\"operator\" type=\"text\" class=\"form-control\">
            </div>
            <span class=\"help-block col-md-3\">操作者</span>
        </div>
        <div class=\"form-group\">
            <div class=\"col-md-offset-2 col-md-10\">
                <button data-request-routing=\"api_cash_withdraw_lock\" data-request-type=\"PUT\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:lock.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  44 => 14,  42 => 13,  31 => 4,  28 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:lock.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/lock.html.twig");
    }
}
