<?php

/* ::base.html.twig */
class __TwigTemplate_08b6c8179bca7ae8acdb84dd1dc17b52c321da344063619af48df563951225fd extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'head_style' => array($this, 'block_head_style'),
            'navbar' => array($this, 'block_navbar'),
            'body' => array($this, 'block_body'),
            'javascripts' => array($this, 'block_javascripts'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!DOCTYPE html>
<html lang=\"en\">
    <head>
        <meta charset=\"utf-8\">
        <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
        <title>";
        // line 7
        $this->displayBlock('title', $context, $blocks);
        echo "</title>

        ";
        // line 10
        echo "        ";
        // line 11
        echo "        <!--[if lt IE 9]>
          <script src=\"https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js\"></script>
          <script src=\"https://oss.maxcdn.com/respond/1.4.2/respond.min.js\"></script>
        <![endif]-->

        ";
        // line 17
        echo "        <link rel=\"stylesheet\" href=\"//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css\">

        ";
        // line 20
        echo "        <link rel=\"stylesheet\" href=\"//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css\">

        ";
        // line 22
        $this->displayBlock('head_style', $context, $blocks);
        // line 23
        echo "
        <style>
            body { padding-top: 70px; }
        </style>
    </head>
    <body>
        <div class=\"container\">
            <div class=\"row\">
                ";
        // line 31
        $this->displayBlock('navbar', $context, $blocks);
        // line 32
        echo "            </div>
            <div class=\"row\">
                ";
        // line 34
        $this->displayBlock('body', $context, $blocks);
        // line 35
        echo "            </div>
            <div class=\"row\">
                <p>&copy;2012 BB</p>
            </div>
        </div>

        ";
        // line 42
        echo "        <script src=\"//code.jquery.com/jquery-1.10.2.min.js\"></script>

        ";
        // line 45
        echo "        <script type=\"text/javascript\" src=\"";
        echo twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\AssetExtension')->getAssetUrl("bundles/fosjsrouting/js/router.js"), "html", null, true);
        echo "\"></script>
        <script type=\"text/javascript\" src=\"";
        // line 46
        echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("fos_js_routing_js", array("callback" => "fos.Router.setData"));
        echo "\"></script>

        ";
        // line 49
        echo "        <script src=\"//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js\"></script>

        ";
        // line 51
        $this->displayBlock('javascripts', $context, $blocks);
        // line 52
        echo "    </body>
</html>
";
    }

    // line 7
    public function block_title($context, array $blocks = array())
    {
        echo "Welcome to Durian2!";
    }

    // line 22
    public function block_head_style($context, array $blocks = array())
    {
    }

    // line 31
    public function block_navbar($context, array $blocks = array())
    {
    }

    // line 34
    public function block_body($context, array $blocks = array())
    {
    }

    // line 51
    public function block_javascripts($context, array $blocks = array())
    {
    }

    public function getTemplateName()
    {
        return "::base.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  129 => 51,  124 => 34,  119 => 31,  114 => 22,  108 => 7,  102 => 52,  100 => 51,  96 => 49,  91 => 46,  86 => 45,  82 => 42,  74 => 35,  72 => 34,  68 => 32,  66 => 31,  56 => 23,  54 => 22,  50 => 20,  46 => 17,  39 => 11,  37 => 10,  32 => 7,  24 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "::base.html.twig", "/var/www/html/app/Resources/views/base.html.twig");
    }
}
