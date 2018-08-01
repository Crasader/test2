<?php

/* BBDurianBundle:Default/Demo/:portal.html.twig */
class __TwigTemplate_8ac6b5ef72d92afd448c660999716e9faa7c916bf0ff74e8067b3254bcf92be3 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default:index.html.twig", "BBDurianBundle:Default/Demo/:portal.html.twig", 1);
        $this->blocks = array(
            'head_style' => array($this, 'block_head_style'),
            'javascripts' => array($this, 'block_javascripts'),
            'body' => array($this, 'block_body'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "BBDurianBundle:Default:index.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_head_style($context, array $blocks = array())
    {
        // line 4
        echo "<style type=\"text/css\">
    #group {
        column-count: 4;
        -moz-column-count: 4;
        -webkit-column-count: 4;
    }

    .list-group-item {
        line-height: 30px;
        padding: 0;
        margin-right: 0;
        margin-left: 0;
    }

    .label {
        float: left;
        line-height: 30px;
        width: 5em;
        padding: 0;
    }

    [data-method=post] .label {
        background-color: #5cb85c;
    }

    [data-method=get] .label {
        background-color: #337ab7;
    }

    [data-method=put] .label {
        background-color: #f0ad4e;
    }

    [data-method=delete] .label {
        background-color: #d9534f;
    }
</style>
";
    }

    // line 43
    public function block_javascripts($context, array $blocks = array())
    {
        // line 44
        echo "<script type=\"text/javascript\">
    \$(function () {
        // 排序按鈕
        var order = { method: false, route: false };
        \$(\"[id*='sorter']\").click(function() {
            var sorter = \$(this).attr('data-sorter');
            order[sorter] = !order[sorter];
            sortList(sorter, order[sorter]);

            \$(\"[id*='sorter']\").children().hide();
            \$(this).children().show();
            \$(this).children().toggleClass('dropup');
        });

        // method選單
        \$('#method').change(function() {
            var group = \$('#group-toggle').val();
            var method = \$(this).val();
            window.location.hash = group + ':' + method;
        });

        // group選單
        \$('#group').find('a').click(function(e) {
            var group = \$(this).attr('href').substring(1);
            var method = \$('#method').val();
            window.location.hash = group + ':' + method;

            e.preventDefault();
        });

        // 網址列的hash改變時做篩選動作
        \$(window).on('hashchange', function() {
            filterList();
        });

        filterList();

        var offset = 300;
        var duration = 500;
        \$(window).scroll(function() {
            if (\$(this).scrollTop() > offset) {
                \$('a.back-to-top').fadeIn(duration);
            } else {
                \$('a.back-to-top').fadeOut(duration);
            }
        });

        \$('a.back-to-top').click(function(e) {
            e.preventDefault();
            \$('html, body').animate({ scrollTop: 0 }, duration);
            return false;
        });
    });

    function filterList() {
        var list = \$('#api-list');
        var group = window.location.hash.substring(1).split(':')[0];
        var method = window.location.hash.substring(1).split(':')[1] || '';

        // 設定group選單
        var selectedGroup = \$('#group').find('a[href=\"#' + group + '\"]');
        var text = selectedGroup.text() + ' <span class=\"caret\"></span>';
        \$('#group-toggle').html(text).val(group);
        \$('#group').find('li').removeClass('active');
        selectedGroup.parent().addClass('active');

        // 設定method選單
        \$('#method').val(method);

        list.children('li').show();
        if (group !== '') {
            list.find('li[data-group!=\"' + group +'\"]').hide();
        }

        if (method !== '') {
            list.find('li[data-method!=\"' + method +'\"]').hide();
        }
    }

    function sortList(sorter, asc) {
        var list = \$('#api-list');
        var item = list.children('li');

        item.sort(function(a, b) {
            var value1 = a.getAttribute('data-' + sorter);
            var value2 = b.getAttribute('data-' + sorter);

            if (asc) {
                return strcmp(value1, value2);
            }

            return strcmp(value2, value1);
        });

        item.detach().appendTo(list);
    }

    function strcmp(a, b) {
        if (a > b) {
            return 1;
        }

        if (a < b) {
            return -1;
        }

        return 0;
    }
</script>
";
    }

    // line 155
    public function block_body($context, array $blocks = array())
    {
        // line 156
        echo "    <form class=\"form-horizontal\">
        <div class=\"form-group\">
            <div class=\"col-md-2\">
                <select id=\"method\" class=\"form-control\">
                    <option value=\"\">All Method</option>
                    <option value=\"get\">GET</option>
                    <option value=\"post\">POST</option>
                    <option value=\"put\">PUT</option>
                    <option value=\"delete\">DELETE</option>
                </select>
            </div>
            <div class=\"col-md-2\">
                <div class=\"dropdown\">
                    <button id=\"group-toggle\" style=\"width:100%;\" class=\"btn btn-default dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\">
                        All Group <span class=\"caret\"></span>
                    </button>
                    <ul id=\"group\" class=\"dropdown-menu\">
                        <li class=\"active\"><a href=\"#\">All Group</a></li>
                    ";
        // line 174
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["api"] ?? null));
        foreach ($context['_seq'] as $context["key"] => $context["group"]) {
            // line 175
            echo "                        <li><a href=\"#";
            echo twig_escape_filter($this->env, $context["key"], "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["group"], "label", array()), "html", null, true);
            echo "</a></li>
                    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['key'], $context['group'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 177
        echo "                    </ul>
                </div>
            </div>
            <div class=\"col-md-2 col-md-offset-4 text-center\">
                <span id=\"method-sorter\" data-sorter=\"method\" class=\"form-control\" style=\"cursor:pointer; padding:6px 0;\">
                    依請求方法排序 <span style=\"display:none;\" class=\"order\"><span class=\"caret\"></span></span>
                </span>
            </div>
            <div class=\"col-md-2 text-center\">
                <span id=\"route-sorter\" data-sorter=\"route\" class=\"form-control\" style=\"cursor:pointer; padding:6px 0;\">
                    依路徑排序 <span style=\"display:none;\" class=\"order\"><span class=\"caret\"></span></span>
                </span>
            </div>
        </div>
    </form>
    <br>

    <ul id=\"api-list\" class=\"list-group\">
    ";
        // line 195
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["api"] ?? null));
        foreach ($context['_seq'] as $context["gkey"] => $context["group"]) {
            // line 196
            echo "        ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->getSourceContext(), $context["group"], "sub", array()));
            foreach ($context['_seq'] as $context["ikey"] => $context["sub"]) {
                // line 197
                echo "            <li class=\"list-group-item row\" data-method=\"";
                echo twig_escape_filter($this->env, twig_lower_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "method", array())), "html", null, true);
                echo "\" data-route=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "route", array()), "html", null, true);
                echo "\" data-group=\"";
                echo twig_escape_filter($this->env, $context["gkey"], "html", null, true);
                echo "\">
                <a href=\"";
                // line 198
                echo twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("demo", array("group" => $context["gkey"], "item" => $context["ikey"])), "html", null, true);
                echo "\">
                    <div class=\"col-md-6\" style=\"padding-left:0;\">
                        <span class=\"label\">";
                // line 200
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "method", array()), "html", null, true);
                echo "</span>
                        <span style=\"margin-left:10px;\"><code>";
                // line 201
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "route", array()), "html", null, true);
                echo "</code></span>
                    </div>
                    <div class=\"col-md-6\">
                        <em style=\"color:#222;\">";
                // line 204
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "name", array()), "html", null, true);
                echo "</em>
                        <span class=\"pull-right\">
                            <small class=\"text-muted\">";
                // line 206
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), $context["sub"], "description", array()), "html", null, true);
                echo "</small>
                        </span>
                    </div>
                </a>
            </li>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['ikey'], $context['sub'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 212
            echo "    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['gkey'], $context['group'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 213
        echo "    </ul>

    <a style=\"position:fixed; bottom:2em; right:0; display:none;\" class=\"back-to-top\" href=\"#\">Back to Top</a>
";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo/:portal.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  304 => 213,  298 => 212,  286 => 206,  281 => 204,  275 => 201,  271 => 200,  266 => 198,  257 => 197,  252 => 196,  248 => 195,  228 => 177,  217 => 175,  213 => 174,  193 => 156,  190 => 155,  77 => 44,  74 => 43,  33 => 4,  30 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo/:portal.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo//portal.html.twig");
    }
}
