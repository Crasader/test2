<?php

/* BBDurianBundle:Default/Demo/Withdraw:getEntry.html.twig */
class __TwigTemplate_25b74c1f752594b9099e0291c6b2f8e2fd49dae048cafd31e93ae4c10beee479 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getEntry.html.twig", 1);
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
    <h1>Get Cash Withdraw Entry By Entry Id</h1>
    <code>GET /api/cash/withdraw/{id}</code>
</div>

<div class=\"row\">
    <div class=\"col-md-7\">
        <p class=\"lead\">帶入明細ID回傳現金出款記錄</p>
    </div>
    ";
        // line 13
        $this->loadTemplate("BBDurianBundle:Default/Doc:sensitive_data.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getEntry.html.twig", 13)->display($context);
        // line 14
        echo "</div>
<div class=\"alert alert-info\">回傳balance已扣除pre_sub預扣，另顯示pre_sub預扣 pre_add預存</div>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <label class=\"control-label col-md-2\"></label>
            <div class=\"col-md-6\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">/api/cash/withdraw/</span>
                    <input data-request-param=\"id\" type=\"text\" class=\"form-control\" placeholder=\"{id}\">
                </div>
            </div>
            <p class=\"help-block col-md-offset-2 col-md-10\">網址變數{id}輸入出款明細ID</p>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">sub_ret</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"sub_ret\" type=\"text\" class=\"form-control\" value=\"0\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">是否回傳附屬資訊。預設0</span>
        </div>
        <div class=\"form-group\">
            <div class=\"col-md-offset-2 col-md-10\">
                <button data-request-routing=\"api_cash_get_withdraw_entry_by_id\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:getEntry.html.twig";
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
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:getEntry.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/getEntry.html.twig");
    }
}
