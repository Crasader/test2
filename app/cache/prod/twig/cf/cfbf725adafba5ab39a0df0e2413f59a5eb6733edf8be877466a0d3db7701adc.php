<?php

/* BBDurianBundle:Default/Demo/Withdraw:getEntries.html.twig */
class __TwigTemplate_bdd6c2d7f11d8789496625bc410353aa950253b71e070e0026daa6db3040f86d extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getEntries.html.twig", 1);
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
    <h1>Get Cash Withdraw Entries</h1>
    <code>GET /api/user/{userId}/cash/withdraw</code>
</div>

<div class=\"row\">
    <div class=\"col-md-7\">
        <p class=\"lead\">查詢現金出款記錄</p>
    </div>
    ";
        // line 13
        $this->loadTemplate("BBDurianBundle:Default/Doc:sensitive_data.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getEntries.html.twig", 13)->display($context);
        // line 14
        echo "</div>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <label class=\"control-label col-md-2\"></label>
            <div class=\"col-md-6\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">/api/user</span>
                    <input data-request-param=\"userId\" type=\"text\" class=\"form-control\" placeholder=\"{userId}\">
                    <span class=\"input-group-addon\">/cash/withdraw</span>
                </div>
            </div>
            <p class=\"help-block col-md-offset-2 col-md-10\">輸入網址變數{userId}</p>
        </div>
        <div class=\"form-group\">
            <button class=\"btn btn-default action-more\" type=\"button\">複製參數</button>
            <div class=\"template\">
                <label class=\"control-label col-md-3\">status[]</label>
                <div class=\"col-md-5\">
                    <div class=\"input-group\">
                        <span class=\"input-group-addon\">
                            <input class=\"action-enable\" type=\"checkbox\">
                        </span>
                        <input name=\"status[]\" type=\"text\" class=\"form-control\" value=\"0\" disabled>
                    </div>
                </div>
                <span class=\"help-block col-md-4\">狀態</span>
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
            <label class=\"control-label col-md-3\">start</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"start\" type=\"text\" class=\"form-control\" value=\"";
        // line 75
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋開始時間。ISO-8601格式</span>
            <label class=\"control-label col-md-3\">end</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"end\" type=\"text\" class=\"form-control\" value=\"";
        // line 85
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋結束時間。ISO-8601格式</span>
        </div>
        <hr>
        <div class=\"form-group\">
            <button class=\"btn btn-default action-more\" type=\"button\">複製參數</button>
            <div class=\"template\">
                <label class=\"control-label col-md-3\">sort[]</label>
                <div class=\"col-md-5\">
                    <div class=\"input-group\">
                        <span class=\"input-group-addon\">
                            <input class=\"action-enable\" type=\"checkbox\">
                        </span>
                        <input name=\"sort[]\" type=\"text\" class=\"form-control\" value=\"id\" disabled>
                    </div>
                </div>
                <span class=\"help-block col-md-4\">排序欄位</span>
                <label class=\"control-label col-md-3\">order[]</label>
                <div class=\"col-md-5\">
                    <div class=\"input-group\">
                        <span class=\"input-group-addon\">
                            <input class=\"action-enable\" type=\"checkbox\">
                        </span>
                        <input name=\"order[]\" type=\"text\" class=\"form-control\" value=\"asc\" disabled>
                    </div>
                </div>
                <span class=\"help-block col-md-4\">升冪或降冪。可設定asc或desc</span>
            </div>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">first_result</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"first_result\" type=\"text\" class=\"form-control\" value=\"0\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">分頁從第幾筆開始</span>
            <label class=\"control-label col-md-3\">max_results</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"max_results\" type=\"text\" class=\"form-control\" value=\"20\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">分頁顯示筆數</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">sub_total</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"sub_total\" type=\"text\" class=\"form-control\" value=\"0\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">是否回傳小計與總計。預設0</span>
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
                <button data-request-routing=\"api_cash_get_withdraw_entry\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:getEntries.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  120 => 85,  107 => 75,  44 => 14,  42 => 13,  31 => 4,  28 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:getEntries.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/getEntries.html.twig");
    }
}
