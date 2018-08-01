<?php

/* BBDurianBundle:Default/Demo:sidebar.html.twig */
class __TwigTemplate_ba27cba3a4045507cb312582e0e70f3c1cb2be2beda08a8670a1a18e0eaefdcf extends Twig_Template
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
        echo "<div class=\"panel panel-default\">
    <div class=\"panel-body\">
        <p class=\"lead\">Related APIs</p>
        <ul class=\"nav nav-pills nav-stacked\">
        ";
        // line 5
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["api"] ?? null));
        foreach ($context['_seq'] as $context["gkey"] => $context["group"]) {
            // line 6
            echo "            ";
            if ((twig_get_attribute($this->env, $this->getSourceContext(), $context["group"], "active", array(), "any", true, true) && twig_get_attribute($this->env, $this->getSourceContext(), $context["group"], "active", array()))) {
                // line 7
                echo "                ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->getSourceContext(), $context["group"], "sub", array()));
                foreach ($context['_seq'] as $context["ikey"] => $context["sub"]) {
                    // line 8
                    echo "                    <li class=\"dropdown";
                    if ((twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "active", array(), "any", true, true) && twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "active", array()))) {
                        echo " active";
                    }
                    echo "\">
                        <a href=\"";
                    // line 9
                    echo twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("demo", array("group" => $context["gkey"], "item" => $context["ikey"])), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "label", array()), "html", null, true);
                    echo "</a>
                    </li>
                ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['ikey'], $context['sub'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 12
                echo "            ";
            }
            // line 13
            echo "        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['gkey'], $context['group'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 14
        echo "        </ul>
    </div>
</div>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo:sidebar.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  64 => 14,  58 => 13,  55 => 12,  44 => 9,  37 => 8,  32 => 7,  29 => 6,  25 => 5,  19 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo:sidebar.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/sidebar.html.twig");
    }
}
