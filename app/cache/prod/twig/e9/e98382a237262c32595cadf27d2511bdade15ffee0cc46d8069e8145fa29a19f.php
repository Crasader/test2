<?php

/* BBDurianBundle:Default/Doc:sensitive_data.html.twig */
class __TwigTemplate_ac09d6fedd2a4abea6068b03a02168cfe54d759bf64c585d335459c566976e5e extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<div class=\"col-md-5\">
    <table class=\"table table-striped table-condensed\">
        為記錄對敏感資料進行操作的使用者相關資訊，需帶入下列參數至RequestHeaders中的Sensitive-Data：
        <thead>
            <tr>
                <th>參數</th><th>說明</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>entrance</td><td>(1)控端 (2)管端 (3)會員端 (4)iTalking (5)Demo頁面 (6)背景 (7)客服後台</td>
            </tr>
            <tr>
                <td>operator_id</td><td>操作敏感資料的操作者id</td>
            </tr>
            <tr>
                <td>operator</td><td>操作敏感資料的操作者名稱</td>
            </tr>
            <tr>
                <td>client_ip</td><td>操作者ip</td>
            </tr>
            <tr>
                <td>run_php</td><td>執行php程式</td>
            </tr>
            <tr>
                <td>vendor</td><td>呼叫api來源 (rda、rdc、rde)</td>
            </tr>
        </tbody>
    </table>
</div>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Doc:sensitive_data.html.twig";
    }

    public function getDebugInfo()
    {
        return array (  19 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Doc:sensitive_data.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Doc/sensitive_data.html.twig");
    }
}
