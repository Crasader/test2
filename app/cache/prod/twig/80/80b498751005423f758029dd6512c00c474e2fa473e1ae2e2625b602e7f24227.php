<?php

/* BBDurianBundle:Default/Demo/Withdraw:confirm.html.twig */
class __TwigTemplate_37bc8f36511cfd3e24a230f63aa09b80a91f8092bc974c4c9bed3887f16d6edd extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:confirm.html.twig", 1);
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
    <h1>Withdraw Confirm</h1>
    <code>PUT /api/cash/withdraw/{id}</code>
</div>

<p class=\"lead\">確認出款</p>
<p>出款狀態可帶入：0:未處理, 1:確認出款, 2:取消出款, 3:拒絕出款</p>
<div class=\"alert alert-info\">自動出款需指定出款商家</div>

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
            <p class=\"help-block col-md-offset-3 col-md-9\">網址變數{id}輸入出款明細ID</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">status</label>
            <div class=\"col-md-5\">
                <input name=\"status\" type=\"text\" class=\"form-control\">
            </div>
            <span class=\"help-block col-md-3\">出款狀態</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">checked_username</label>
            <div class=\"col-md-5\">
                <input name=\"checked_username\" type=\"text\" class=\"form-control\">
            </div>
            <span class=\"help-block col-md-3\">操作者名稱</span>
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
            <label class=\"control-label col-md-3\">manual</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"manual\" type=\"text\" class=\"form-control\" value = \"0\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">人工確認出款狀態</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">force</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"force\" type=\"text\" class=\"form-control\" value = \"0\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">強制修改狀態(控端及廳主帳號用)</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">system</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"system\" type=\"text\" class=\"form-control\" value = \"0\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">系統操作修改狀態</span>
        </div>
        <div class=\"form-group\">
            <div class=\"col-md-offset-2 col-md-10\">
                <button data-request-routing=\"api_cash_withdraw_confirm\" data-request-type=\"PUT\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:confirm.html.twig";
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
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:confirm.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/confirm.html.twig");
    }
}
