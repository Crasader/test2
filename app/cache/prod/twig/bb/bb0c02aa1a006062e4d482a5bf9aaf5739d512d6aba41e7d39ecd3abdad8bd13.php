<?php

/* BBDurianBundle:Default/Demo:index.html.twig */
class __TwigTemplate_ecf0bc57686ed818e068a7327dea968dd0acca0c9a592a55853f3e15c4a03706 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("BBDurianBundle:Default:index.html.twig", "BBDurianBundle:Default/Demo:index.html.twig", 1);
        $this->blocks = array(
            'head_style' => array($this, 'block_head_style'),
            'javascripts' => array($this, 'block_javascripts'),
            'body' => array($this, 'block_body'),
            'content' => array($this, 'block_content'),
            'sidebar' => array($this, 'block_sidebar'),
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
        $this->displayParentBlock("head_style", $context, $blocks);
        echo "
";
        // line 6
        echo "<link rel=\"stylesheet\" href=\"//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.4/styles/github.min.css\"/>
";
    }

    // line 9
    public function block_javascripts($context, array $blocks = array())
    {
        // line 10
        echo "<script type=\"text/javascript\" src=\"//ajax.cdnjs.com/ajax/libs/json2/20110223/json2.js\"></script>

";
        // line 13
        echo "<script type=\"text/javascript\" src=\"//cdnjs.cloudflare.com/ajax/libs/highlight.js/7.4/highlight.min.js\"></script>

<script type=\"text/javascript\">
";
        // line 17
        echo "var enableHandler = function() {
    var el = \$(this);
    var inputs;

    inputs = el.closest('.form-group').find('input[type=\"text\"]');

    if (el.prop('checked')) {
        inputs.prop('disabled', false);
    } else {
        inputs.attr('disabled', true);
    }

    inputs = el.closest('.form-group').find('input[type=\"checkbox\"]');

    if (el.prop('checked')) {
        inputs.prop('checked', true);
    } else {
        inputs.prop('checked', false);
    }
};

";
        // line 39
        echo "\$(function () {
    \$('.btn-primary').click(function() {
        var btn = \$(this);
        var routing = btn.data('request-routing');
        var type = btn.data('request-type');
        var processData = true;
        var contentType = 'application/x-www-form-urlencoded; charset=UTF-8';

        if (btn.attr('data-request-processData') == \"false\") {
            processData = false;
        }

        if (btn.attr('data-request-contentType') == \"false\") {
            contentType = false;
        }

        var params = {};
        var error = 0;

        ";
        // line 59
        echo "        if (!type) {
            type = 'GET';
        }

        btn.button('loading');

        ";
        // line 66
        echo "        \$('[data-request-param]').each(function() {
            var e = \$(this);

            var key = e.data('request-param');
            var val = e.val();
            var uncheck = e.data('request-uncheck');

            params[key] = val;

            if (!uncheck) {
                e.closest('.form-group').removeClass('has-error');
            }

            ";
        // line 80
        echo "            if (!uncheck && val === '') {
                e.closest('.form-group').addClass('has-error');
                error++;
            }
        });

        if (error !== 0) {
            btn.button('reset');
            return;
        }

        ";
        // line 92
        echo "        var sensitive = getSensitvieData();

        var data = \$('form:not([data-http-header])').serialize();
        var headers = \$('form[data-http-header]').serializeArray();

        ";
        // line 98
        echo "        \$('[data-header-param]').each(function() {
            var e = \$(this);

            var name = e.attr('name');
            var val = e.val();

            headers.push({
                name: name,
                value: val
            });
        });

        ";
        // line 111
        echo "        if (\$.isFunction(window.resetDataParam)) {
            data = resetDataParam();
        }

        \$.ajax({
            url: Routing.generate(routing, params),
            type: type,
            data: data,
            dataType: 'json',
            contentType: contentType,
            processData: processData,
            beforeSend: function(jqXHR, settings) {
                jqXHR.setRequestHeader(\"Sensitive-Data\", \$.param(sensitive));

                \$.each(headers, function (i, header) {
                    jqXHR.setRequestHeader(header.name, header.value);
                });

                var obj = {};
                \$.each(['url', 'type', 'contentType', 'data'], function(index, val) {
                    obj[val] = settings[val];
                });

                var target = \$('#request-dump');
                dump_json(obj, target);
            },
            success: function(data, textStatus, jqXHR) {
                var target = \$('#response-dump');
                dump_json(data, target);
            },
            error: function(jqXHR) {
                var obj = JSON.parse(jqXHR.responseText);

                var target = \$('#response-dump');
                dump_json(obj, target);
            },
            complete: function(jqXHR) {
                refreshWdt(jqXHR.getResponseHeader('x-debug-token'));
                btn.button('reset');
            }
        });
    });

    ";
        // line 155
        echo "    \$('#load-btn').click(function() {
        var btn = \$(this);
        var routing = btn.data('request-routing');
        var type = btn.data('request-type');
        var params = {};
        var error = 0;

        ";
        // line 163
        echo "        if (!type) {
            type = 'GET';
        }

        btn.button('loading');

        ";
        // line 170
        echo "        \$('[data-request-param]').each(function() {
            var e = \$(this);

            var key = e.data('request-param');
            var val = e.val();
            var uncheck = e.data('request-uncheck');

            params[key] = val;

            if (!uncheck) {
                e.closest('.form-group').removeClass('has-error');
            }

            ";
        // line 184
        echo "            if (!uncheck && val === '') {
                e.closest('.form-group').addClass('has-error');
                error++;
            }
        });

        if (error > 0) {
            btn.button('reset');
            return;
        }

        ";
        // line 196
        echo "        var sensitive = getSensitvieData();

        var data = \$('form').serialize();

        ";
        // line 201
        echo "        if (\$.isFunction(window.resetDataParam)) {
            data = resetDataParam();
        }

        \$.ajax({
            url: Routing.generate(routing, params),
            type: type,
            data: data,
            dataType: 'json',
            beforeSend: function(jqXHR, settings) {
                jqXHR.setRequestHeader(\"Sensitive-Data\", \$.param(sensitive));

                var obj = {};
                \$.each(['url', 'type', 'contentType', 'data'], function(index, val) {
                    obj[val] = settings[val];
                });

                var target = \$('#request-dump');
                dump_json(obj, target);
            },
            success: function(data, textStatus, jqXHR) {
                var target = \$('#response-dump');
                dump_json(data, target);

                if (data['result'] === 'ok') {
                    doLoad(data['ret']);

                    ";
        // line 229
        echo "                    if (\$.isFunction(window.reLoad)) {
                        reLoad(doLoad(data['ret'][0]));
                    }
                }
            },
            error: function(jqXHR) {
                var obj = JSON.parse(jqXHR.responseText);

                var target = \$('#response-dump');
                dump_json(obj, target);
            },
            complete: function(jqXHR) {
                refreshWdt(jqXHR.getResponseHeader('x-debug-token'));
                btn.button('reset');
            }
        });
    });

    ";
        // line 248
        echo "    \$('.action-enable').change(enableHandler);

    ";
        // line 251
        echo "    \$('.action-more').click(function () {
        var el = \$(this);
        var root = el.closest('div');
        var template = root.find('.template');

        var count = template.data('count');
        if (count === undefined) {
            count = 0;
        }
        count++;
        template.data('count', count);

        var clone = template.clone();
        clone.removeClass('template');
        clone.find('input[type=text]').val('');

        var str = clone.html().replace(/\\[[0-9]+\\]/g, '[' + count + ']');
        clone.html(str);
        clone.find('.action-enable').change(enableHandler);

        root.append(clone);
    });
});

";
        // line 276
        echo "function dump_json(data, target) {
    var str = JSON.stringify(data, null, '\\t');
    target.text(str);
    hljs.highlightBlock(target[0]);
}

";
        // line 283
        echo "function render_tabs(target, enums) {
    var tab = target.find('.nav-tabs > li').first();

    \$.each(enums, function(index, val) {
        var tabClone = tab.clone();

        var ob = tabClone.find('a');

        var str = ob.attr('href').replace('1', val);
        ob.attr('href', str);

        var str = ob.text().replace('1', val);
        ob.text(str);

        if (index === 0) {
            tabClone.addClass('active');
        }

        target.find('.nav-tabs').append(tabClone);
    });

    tab.remove();

    var pan = target.find('.tab-pane').first();

    \$.each(enums, function(index, val) {
        var panClone = pan.clone();

        var str = panClone.attr('id').replace(/1/, val);
        panClone.attr('id', str);

        panClone.find('label').each(function() {
            var ob = \$(this);
            var str = ob.text().replace(/1/g, val);
            ob.text(str);
        });

        panClone.find('input[type=\"text\"]').each(function() {
            var ob = \$(this);
            var str = ob.attr('name').replace(/1/g, val);
            ob.attr('name', str);
        });

        if (index === 0) {
            panClone.addClass('active');
        }

        panClone.find('.action-enable').change(enableHandler);
        target.find('.tab-content').append(panClone);
    });

    pan.remove();
}

";
        // line 338
        echo "function getSensitvieData() {
    var clientIp = \$('input[name=client_ip]').val();
    var pathInfo = \$('input[name=path_info]').val();

    var sensitive = new Object;
    sensitive.entrance = 5;
    sensitive.operator = '';
    sensitive.client_ip = clientIp;
    sensitive.run_php = pathInfo;
    sensitive.operator_id = '';
    sensitive.vendor = 'acc';

    return sensitive;
}

";
        // line 354
        echo "function doLoad(obj) {
    ";
        // line 356
        echo "    \$('input[name]').each(function() {
        var el = \$(this);
        var name = el.attr('name');
        var splitted = name.split(/[\\[\\]]/);

        ";
        // line 362
        echo "        splitted = \$.grep(splitted, function(val) {
            return (val !== '');
        });

        var v = obj;
        \$(splitted).each(function(key, val) {
            if (\$.isPlainObject(v)) {
                v = v[val];
            }
        });

        ";
        // line 374
        echo "        if (\$.isPlainObject(v) && v['date'] !== undefined) {
            v = v['date'];
        }

        ";
        // line 379
        echo "        if (\$.type(v) === 'boolean') {
            v = Number(v);
        }

        el.val(v);
    });
}

";
        // line 388
        echo "function refreshWdt(token)
{
    if (token === null) return;

    var url;

    try {
        // prod 不存在 _wdt 會出錯
        url = Routing.generate('_wdt', {'token': token})
    } catch (err) {
        return
    }

    \$.ajax({
        url: url,
        type: 'GET',
        success: function (msg) {
            \$('div[id^=sfwdt]').html(msg);
        }
    });
}
</script>
";
    }

    // line 411
    public function block_body($context, array $blocks = array())
    {
        // line 412
        echo "    <div class=\"col-md-9\">
        ";
        // line 413
        $this->displayBlock('content', $context, $blocks);
        // line 414
        echo "        <p class=\"help-block\">Request</p>
        <pre><code id=\"request-dump\" class='language-javascript'>{}</code></pre>
        <p class=\"help-block\">Response</p>
        <pre><code id=\"response-dump\" class='language-javascript'>{}</code></pre>
    </div>
    <div class=\"col-md-3\">
        ";
        // line 420
        $this->displayBlock('sidebar', $context, $blocks);
        // line 423
        echo "    </div>
    <div class=\"hidden\">
        <input name=\"client_ip\" type=\"hidden\" value=\"";
        // line 425
        echo twig_escape_filter($this->env, ($context["clientIp"] ?? null), "html", null, true);
        echo "\">
        <input name=\"path_info\" type=\"hidden\" value=\"";
        // line 426
        echo twig_escape_filter($this->env, ($context["pathInfo"] ?? null), "html", null, true);
        echo "\">
    </div>
";
    }

    // line 413
    public function block_content($context, array $blocks = array())
    {
    }

    // line 420
    public function block_sidebar($context, array $blocks = array())
    {
        // line 421
        echo "            ";
        $this->loadTemplate("BBDurianBundle:Default/Demo:sidebar.html.twig", "BBDurianBundle:Default/Demo:index.html.twig", 421)->display($context);
        // line 422
        echo "        ";
    }

    public function getTemplateName()
    {
        return "BBDurianBundle:Default/Demo:index.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  522 => 422,  519 => 421,  516 => 420,  511 => 413,  504 => 426,  500 => 425,  496 => 423,  494 => 420,  486 => 414,  484 => 413,  481 => 412,  478 => 411,  452 => 388,  442 => 379,  436 => 374,  423 => 362,  416 => 356,  413 => 354,  396 => 338,  340 => 283,  332 => 276,  306 => 251,  302 => 248,  282 => 229,  253 => 201,  247 => 196,  234 => 184,  219 => 170,  211 => 163,  202 => 155,  157 => 111,  143 => 98,  136 => 92,  123 => 80,  108 => 66,  100 => 59,  79 => 39,  56 => 17,  51 => 13,  47 => 10,  44 => 9,  39 => 6,  35 => 4,  32 => 3,  11 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BBDurianBundle:Default/Demo:index.html.twig", "/var/www/html/src/BB/DurianBundle/Resources/views/Default/Demo/index.html.twig");
    }
}
