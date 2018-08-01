<?php

/* BBDurianBundle:Default/Demo/Withdraw:getEntriesList.html.twig */
class __TwigTemplate_a9d937e0e7f1682f062a2e399c697826ce5c5ce3a37c4e76177b47e6b6ef97fb extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default/Demo:index.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getEntriesList.html.twig", 1);
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
    <h1>Get Withdraw Entries List</h1>
    <code>GET /api/cash/withdraw/list</code>
</div>

<div class=\"row\">
    <div class=\"col-md-7\">
        <p class=\"lead\">回傳下層出款明細列表</p>
    </div>
    ";
        // line 13
        $this->loadTemplate("BBDurianBundle:Default/Doc:sensitive_data.html.twig", "BBDurianBundle:Default/Demo/Withdraw:getEntriesList.html.twig", 13)->display($context);
        // line 14
        echo "</div>

<div class=\"alert alert-info\">回傳balance已扣除pre_sub預扣，另顯示pre_sub預扣 pre_add預存</div>

<form class=\"form-horizontal\" role=\"form\">
    <fieldset>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">domain</label>
            <div class=\"col-md-5\">
                <input name=\"domain\" type=\"text\" class=\"form-control\">
            </div>
            <span class=\"help-block col-md-4\">廳id</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">parent_id</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"parent_id\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">上層id</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">username</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"username\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">查詢使用者帳號</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">currency</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"currency\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">幣別</span>
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
            <span class=\"help-block col-md-4\">查詢備註</span>
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
            <button class=\"btn btn-default action-more\" type=\"button\">複製參數</button>
            <div class=\"template\">
                <label class=\"control-label col-md-3\">level_id[]</label>
                <div class=\"col-md-5\">
                    <div class=\"input-group\">
                        <span class=\"input-group-addon\">
                            <input class=\"action-enable\" type=\"checkbox\">
                        </span>
                        <input name=\"level_id[]\" type=\"text\" class=\"form-control\" value=\"0\" disabled>
                    </div>
                </div>
                <span class=\"help-block col-md-4\">會員層級</span>
            </div>
        </div>
        <div class=\"form-group\">
            <button class=\"btn btn-default action-more\" type=\"button\">複製參數</button>
            <div class=\"template\">
                <label class=\"control-label col-md-3\">exclude_zero[]</label>
                <div class=\"col-md-5\">
                    <div class=\"input-group\">
                        <span class=\"input-group-addon\">
                            <input class=\"action-enable\" type=\"checkbox\">
                        </span>
                        <input name=\"exclude_zero[]\" type=\"text\" class=\"form-control\" value=\"fee\" disabled>
                    </div>
                </div>
                <span class=\"help-block col-md-4\">排除值為0的欄位</span>
            </div>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">amount_min</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"amount_min\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">出款金額下限</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">amount_max</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"amount_max\" type=\"text\" class=\"form-control\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">出款金額上限</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">created_at_start</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"created_at_start\" type=\"text\" class=\"form-control\" value=\"";
        // line 175
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細新增開始時間。ISO-8601格式</span>
            <label class=\"control-label col-md-3\">created_at_end</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"created_at_end\" type=\"text\" class=\"form-control\" value=\"";
        // line 185
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細新增結束時間。ISO-8601格式</span>
        </div>
        <div class=\"form-group\">
            <label class=\"control-label col-md-3\">confirm_at_start</label>
            <div class=\"col-md-5\">
                <div class=\"input-group\">
                    <span class=\"input-group-addon\">
                        <input class=\"action-enable\" type=\"checkbox\">
                    </span>
                    <input name=\"confirm_at_start\" type=\"text\" class=\"form-control\" value=\"";
        // line 197
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
        // line 207
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, "now", "Y-m-d\\TH:i:sO"), "html", null, true);
        echo "\" disabled>
                </div>
            </div>
            <span class=\"help-block col-md-4\">搜尋明細確認結束時間</span>
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
                <button data-request-routing=\"api_cash_get_withdraw_entry_list\" class=\"btn btn-primary\" type=\"button\" data-loading-text=\"loading...\">Request</button>
            </div>
        </div>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/Withdraw:getEntriesList.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  248 => 207,  235 => 197,  220 => 185,  207 => 175,  44 => 14,  42 => 13,  31 => 4,  28 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo/Withdraw:getEntriesList.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/Withdraw/getEntriesList.html.twig");
    }
}
