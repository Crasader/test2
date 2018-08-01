<?php

/* BBDurianBundle:Default:index.html.twig */
class __TwigTemplate_00302df9cf379635bae9772d38d6c6a5dad24b777284e00efd98e135b41da43d extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("::base.html.twig", "BBDurianBundle:Default:index.html.twig", 1);
        $this->blocks = array(
            'navbar' => array($this, 'block_navbar'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "::base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_navbar($context, array $blocks = array())
    {
        // line 4
        echo "    ";
        $this->loadTemplate("BBDurianBundle:Default:navbar.html.twig", "BBDurianBundle:Default:index.html.twig", 4)->display($context);
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default:index.html.twig";
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
        return new Twig_Source("", "BBDurianBundle:Default:index.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/index.html.twig");
    }
}
