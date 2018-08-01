<?php

/* BBDurianBundle:Default/Demo/Withdraw:getReport.html.twig */
class __TwigTemplate_26a84a9799e58b300e838e63e8d4b983b097bf55b209c2a36da657dc0bdc3e54 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getReport.html.twig", 1);
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
    <h1>Get Withdraw Report</h1>
    <code>GET /api/cash/withdraw/report</code>
</div>

<p class=\"lead\">回傳出款統計資料</p>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <button class=\"btn btn-default action-more\" type=\"button\">複製參數</button>
            <div class=\"template\">
                <label class=\"control-label col-md-3\">users[]</label>
                <div class=\"col-md-5\">
                    <input name=\"users[]\" type=\"text\" class=\"form-control\">
                </div>
                <span class=\"help-block col-md-4\">使用者ID</span>
            </div>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">auto_withdraw</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"auto_withdraw\" type=\"text\" class=\"form-control\" value=\"1\" disabled>
                </div>
           </div>
            <span class=\"help-block col-md-4\">是否為自動出款</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">merchant_withdraw_id</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"merchant_withdraw_id\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">出款商家ID</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">created_at_start</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"created_at_start\" type=\"text\" class=\"form-control\" value=\"";
        // line 54
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">明細的起始時間區間。ISO-8601格式</span>
            <label class=\"control-label col-md-3\">created_at_end</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"created_at_end\" type=\"text\" class=\"form-control\" value=\"";
        // line 64
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">明細的結束時間區間。ISO-8601格式</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">confirm_at_start</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"confirm_at_start\" type=\"text\" class=\"form-control\" value=\"";
        // line 76
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細確認起始時間</span>
            <label class=\"control-label col-md-3\">confirm_at_end</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"confirm_at_end\" type=\"text\" class=\"form-control\" value=\"";
        // line 86
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細確認結束時間</span>
        </div>
        <div class=\"form-group\">
            <div class=\"col-md-offset-2 col-md-10\">
                <button data-request-routing=\"api_cash_get_withdraw_report\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:getReport.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  124 => 86,  111 => 76,  96 => 64,  83 => 54,  31 => 4,  28 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:getReport.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/getReport.html.twig");
    }
}
