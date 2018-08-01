<?php

/* BBDurianBundle:Default/Demo/Withdraw:withdraw.html.twig */
class __TwigTemplate_d0368e1fe2f15b98b4c421cd1f6bec41fc48b30e9e58d72668bd80d1135c5ca4 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:withdraw.html.twig", 1);
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
    <h1>Cash Withdraw</h1>
    <code>POST /api/user/{userId}/cash/withdraw</code>
</div>

<div class=\"row\">
    <div class=\"alert alert-warning\">
        金額相關欄位不能超過小數點4位，否則會回傳150610003例外錯誤
    </div>
    <div class=\"col-md-7\">
        <p class=\"lead\">出款</p>
    </div>
    ";
        // line 16
        $this->loadTemplate("BBDurianBundle:Default/Doc:sensitive_data.html.twig", "BBDurianBundle:Default/Demo/Withdraw:withdraw.html.twig", 16)->display($context);
        // line 17
        echo "</div>
<div class=\"alert alert-info\">
    memo(備註)的字數限制為<code>100</code>個字，若超過只會保留前100個字
</div>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <label class=\"control-label col-md-2\"></label>
            <div class=\"col-md-6\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">/api/user/</span>
                    <input data-request-param=\"userId\" type=\"text\" class=\"form-control\" placeholder=\"{userId}\">
                    <span class=\"input-group-addon\">/cash/withdraw</span>
                </div>
            </div>
            <p class=\"help-block col-md-offset-2 col-md-10\">輸入網址變數{userId}</p>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">bank_id</label>
            <div class=\"col-md-5\">
                <input name=\"bank_id\" type=\"text\" class=\"form-control\">
            </div>
            <span class=\"help-block col-md-4\">出款銀行ID</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">amount</label>
            <div class=\"col-md-5\">
                <input name=\"amount\" type=\"text\" class=\"form-control\">
            </div>
            <span class=\"help-block col-md-4\">出款金額</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">fee</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"fee\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">手續費。預設0</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">deduction</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"deduction\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">優惠扣除額。預設0</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">aduit_fee</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"aduit_fee\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">常態稽核手續費。預設0</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">aduit_charge</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"aduit_charge\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">常態稽核行政費用。預設0</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">payment_gateway_fee</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"payment_gateway_fee\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">支付平台手續費。預設0</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">system_trans</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"system_trans\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">出款系統(給Acoount的參數)。預設0</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">multiple_audit</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"multiple_audit\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">取款倍數稽核(給Acoount的參數)。預設0</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">status_string</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"status_string\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">出款狀態字串(給Acoount的參數)。預設空字串</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">memo</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"memo\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">備註。預設空字串</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">ip</label>
            <div class=\"col-md-5\">
                <input name=\"ip\" type=\"text\" class=\"form-control\">
            </div>
            <span class=\"help-block col-md-4\">出款者IP</span>
        </div>
        <div class=\"form-group\">
            <div class=\"col-md-offset-2 col-md-10\">
                <button data-request-routing=\"api_user_cash_withdraw\" data-request-type=\"POST\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:withdraw.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  47 => 17,  45 => 16,  31 => 4,  28 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:withdraw.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/withdraw.html.twig");
    }
}
