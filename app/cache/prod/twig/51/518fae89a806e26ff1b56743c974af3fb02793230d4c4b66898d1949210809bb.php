<?php

/* BBDurianBundle:Default/Demo/Withdraw:getConfirmedList.html.twig */
class __TwigTemplate_1b03861f66b0cd6bd6ea868e75765afb8c93da12c26faf70a97ad5fa39f5edd9 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getConfirmedList.html.twig", 1);
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
    <h1>Get Withdraw Confirmed List</h1>
    <code>GET /api/cash/withdraw/confirmed_list</code>
</div>

<div class=\"row\">
    <div class=\"col-md-8\">
        <p class=\"lead\">回傳時間內有確認或新增出款明細的使用者</p>
        <p>參考右表出款明細的狀態，預設回傳確認出款的明細</p>
        <div class=\"alert alert-info\">
            至少帶明細確認時間或明細新增時間其中一個搜尋條件，都沒帶則會回傳錯誤
        </div>
    </div>
    <div class=\"col-md-4\">
        <table class=\"table table-condensed\">
            <thead>
                <tr>
                    <th>#</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>0</td><td>未處理</td>
                </tr>
                <tr>
                    <td>1</td><td>確認出款</td>
                </tr>
                <tr>
                    <td>2</td><td>取消出款</td>
                </tr>
                <tr>
                    <td>3</td><td>拒絕出款</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div class=\"alert alert-info\">回傳balance已扣除pre_sub預扣，另顯示pre_sub預扣 pre_add預存</div>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">confirm_at_start</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"confirm_at_start\" type=\"text\" class=\"form-control\" value=\"";
        // line 52
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細確認時間區間</span>
            <label class=\"control-label col-md-3\">confirm_at_end</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"confirm_at_end\" type=\"text\" class=\"form-control\" value=\"";
        // line 62
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細確認時間區間</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">created_at_start</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"created_at_start\" type=\"text\" class=\"form-control\" value=\"";
        // line 74
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細新增時間區間</span>
            <label class=\"control-label col-md-3\">created_at_end</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"created_at_end\" type=\"text\" class=\"form-control\" value=\"";
        // line 84
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細新增時間區間</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">status</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"status\" type=\"text\" class=\"form-control\" value=\"1\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">出款明細狀態, 預設值為\"確認出款\"</span>
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
        <hr>
        <div class=\"form-group\">
            <div class=\"col-md-offset-2 col-md-10\">
                <button data-request-routing=\"api_cash_get_withdraw_confirmed_list\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:getConfirmedList.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  122 => 84,  109 => 74,  94 => 62,  81 => 52,  31 => 4,  28 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:getConfirmedList.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/getConfirmedList.html.twig");
    }
}
