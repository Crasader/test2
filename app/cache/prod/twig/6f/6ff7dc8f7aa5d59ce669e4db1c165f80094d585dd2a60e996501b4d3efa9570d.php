<?php

/* BBDurianBundle:Default/Demo/Withdraw:getStat.html.twig */
class __TwigTemplate_7747493138d0136d3bea73024644601d3eec1826789efaae14feeb57c59db7fa extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getStat.html.twig", 1);
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
    <h1>Get Withdraw Stat By User Id</h1>
    <code>GET /api/user/{userId}/withdraw_stat</code>
</div>

<p class=\"lead\">取得使用者出款統計資料</p>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <label class=\"control-label col-md-2\"></label>
            <div class=\"col-md-6\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">/api/user/</span>
                    <input data-request-param=\"userId\" type=\"text\" class=\"form-control\" placeholder=\"{userId}\">
                </div>
            </div>
            <p class=\"help-block col-md-offset-2 col-md-10\">輸入網址變數{userId}</p>
        </div>
        <div class=\"form-group\">
            <div class=\"col-md-offset-2 col-md-6\">
                <button data-request-routing=\"api_get_user_withdraw_stat\" data-request-type=\"GET\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:getStat.html.twig";
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
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:getStat.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/getStat.html.twig");
    }
}
