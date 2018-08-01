<?php

/* BBDurianBundle:Default:navbar.html.twig */
class __TwigTemplate_4283d55c45b5c8ff8a167142fce23df8ee2762e969a04e5ad2ef13333def76a4 extends Twig_Template
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
        echo "<div class=\"navbar navbar-default navbar-fixed-top\" role=\"navigation\">
    <div class=\"container\">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class=\"navbar-header\">
            <button type=\"button\" class=\"navbar-toggle\" data-toggle=\"collapse\" data-target=\".navbar-durian-collapse\">
                <span class=\"sr-only\">Toggle navigation</span>
                <span class=\"icon-bar\"></span>
                <span class=\"icon-bar\"></span>
                <span class=\"icon-bar\"></span>
            </button>
            <a class=\"navbar-brand\" href=\"";
        // line 11
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("home");
        echo "\">Durian2</a>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class=\"collapse navbar-collapse navbar-durian-collapse\">
            <ul class=\"nav navbar-nav\">
                <li><a href=\"";
        // line 16
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("home");
        echo "\">Home</a></li>
                <li><a href=\"";
        // line 17
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("demo_default");
        echo "\">Demo</a></li>
                <li class=\"dropdown\">
                    <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">Documentation <b class=\"caret\"></b></a>
                    <ul class=\"dropdown-menu\">
                        <li class=\"dropdown-header\">How-to</li>
                        <li><a href=\"";
        // line 22
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "sensitive_data"));
        echo "\">Sensitive Data</a></li>
                        <li class=\"divider\"></li>
                        <li class=\"dropdown-header\">Mapping List</li>
                        <li><a href=\"";
        // line 25
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "exception_map"));
        echo "\">Exception Map</a></li>
                        <li><a href=\"";
        // line 26
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "login_result"));
        echo "\">Login Result</a></li>
                        <li><a href=\"";
        // line 27
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "role_map"));
        echo "\">Role Map</a></li>
                        <li><a href=\"";
        // line 28
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "account_log_status"));
        echo "\">AccountLog Status</a></li>
                        <li><a href=\"";
        // line 29
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "bank_status"));
        echo "\">Bank Status</a></li>
                        <li><a href=\"";
        // line 30
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "credit_group"));
        echo "\">Credit Group</a></li>
                        <li><a href=\"";
        // line 31
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "share_limit_group"));
        echo "\">ShareLimit Group</a></li>
                        <li><a href=\"";
        // line 32
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "currency"));
        echo "\">Currency</a></li>
                        <li><a href=\"";
        // line 33
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "game_map"));
        echo "\">Game Map</a></li>
                        <li><a href=\"";
        // line 34
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "order_strategy"));
        echo "\">Order Strategy</a></li>
                        <li><a href=\"";
        // line 35
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "merchant_order_strategy"));
        echo "\">Merchant Order Strategy</a></li>
                        <li><a href=\"";
        // line 36
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "italking_map"));
        echo "\">ITalking Map</a></li>
                        <li><a href=\"";
        // line 37
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "deposit_payway"));
        echo "\">Deposit Payway</a></li>
                        <li><a href=\"";
        // line 38
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "client_os_map"));
        echo "\">Client OS Map</a></li>
                        <li><a href=\"";
        // line 39
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "client_browser_map"));
        echo "\">Client Browser Map</a></li>
                        <li><a href=\"";
        // line 40
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "ingress_map"));
        echo "\">Ingress Map</a></li>
                        <li><a href=\"";
        // line 41
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "language_map"));
        echo "\">Language Map</a></li>
                        <li><a href=\"";
        // line 42
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "outside_group_map"));
        echo "\">Outside Group Map</a></li>
                        <li class=\"divider\"></li>
                        <li class=\"dropdown-header\">Opcode</li>
                        <li><a href=\"";
        // line 45
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "opcode/opcode"));
        echo "\">All</a></li>
                        <li><a href=\"";
        // line 46
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "opcode/allow_amount_zero"));
        echo "\">Allow Amount Zero</a></li>
                        <li><a href=\"";
        // line 47
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "opcode/allow_balance_negative"));
        echo "\">Allow Balance Negative</a></li>
                        <li><a href=\"";
        // line 48
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("doc", array("item" => "opcode/disable_not_allow"));
        echo "\">Disable Not Allow</a></li>
                    </ul>
                </li>
                <li class=\"dropdown\">
                    <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">Tools <b class=\"caret\"></b></a>
                    <ul class=\"dropdown-menu\">
                        <li><a href=\"";
        // line 54
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_check");
        echo "\">Check</a></li>
                        <li><a href=\"";
        // line 55
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_check_speed");
        echo "\">Check Speed</a></li>
                        <li><a href=\"";
        // line 56
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_domain_map");
        echo "\">Domain Map</a></li>
                        <li><a href=\"";
        // line 57
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_deposit_check");
        echo "\">Deposit Check</a></li>
                        <li><a href=\"";
        // line 58
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_display_background_process_name");
        echo "\">Set Background Process</a></li>
                        <li><a href=\"";
        // line 59
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_revise_entry");
        echo "\">Revise Entry</a></li>
                        <li><a href=\"";
        // line 60
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_repair_entry_page");
        echo "\">Repair Entry</a></li>
                        <li><a href=\"";
        // line 61
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_display_ip_blacklist");
        echo "\">Ip Blacklist</a></li>
                        <li><a href=\"";
        // line 62
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_display_kue_job");
        echo "\">Manage Kue Job</a></li>
                        <li><a href=\"";
        // line 63
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("tools_set_random_float_vendor");
        echo "\">Set Random Float Vendor</a></li>
                    </ul>
                </li>
                <li class=\"dropdown\">
                    <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">Log <b class=\"caret\"></b></a>
                    <ul class=\"dropdown-menu\">
                        <li><a href=\"";
        // line 69
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("log_operation");
        echo "\">LogOperation</a></li>

                    </ul>
                </li>
            </ul>
            <p class=\"navbar-text pull-right\">
                ";
        // line 75
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\HttpKernelExtension')->renderFragment($this->env->getExtension('Symfony\Bridge\Twig\Extension\HttpKernelExtension')->controller("BBDurianBundle:Default:version"));
        echo "
            </p>
        </div>
    </div>
</div>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default:navbar.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  206 => 75,  197 => 69,  188 => 63,  184 => 62,  180 => 61,  176 => 60,  172 => 59,  168 => 58,  164 => 57,  160 => 56,  156 => 55,  152 => 54,  143 => 48,  139 => 47,  135 => 46,  131 => 45,  125 => 42,  121 => 41,  117 => 40,  113 => 39,  109 => 38,  105 => 37,  101 => 36,  97 => 35,  93 => 34,  89 => 33,  85 => 32,  81 => 31,  77 => 30,  73 => 29,  69 => 28,  65 => 27,  61 => 26,  57 => 25,  51 => 22,  43 => 17,  39 => 16,  31 => 11,  19 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default:navbar.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/navbar.html.twig");
    }
}
