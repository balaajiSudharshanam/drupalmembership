<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* modules/contrib/webform/templates/webform-html-editor-markup.html.twig */
class __TwigTemplate_7b1b0199259f830629ef8baed610456d extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 21
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["content"] ?? null), 21, $this->source), "html", null, true);
    }

    public function getTemplateName()
    {
        return "modules/contrib/webform/templates/webform-html-editor-markup.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  39 => 21,);
    }

    public function getSourceContext()
    {
        return new Source("{#
/**
 * @file
 * Theme implementation for webform html editor markup.
 *
 * Available variables
 * - markup: HTML markup.
 * - allowed_tags: Allowed tags.
 * - content: Renderable HTML markup.
 *
 * Using Twig output modifer to remove extra carriage returns at the end of
 * the generated markup.
 *
 * @see template_preprocess_webform_html_editor_markup()
 * @see \\Drupal\\webform\\Element\\WebformHtmlEditor::checkMarkup
 * @see https://www.drupal.org/project/drupal/issues/2245901
 * @see https://twig.symfony.com/doc/2.x/templates.html#whitespace-control
 * @ingroup themeable
 */
#}
{{- content -}}
", "modules/contrib/webform/templates/webform-html-editor-markup.html.twig", "C:\\xampp\\htdocs\\membership\\web\\modules\\contrib\\webform\\templates\\webform-html-editor-markup.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array();
        static $filters = array("escape" => 21);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                [],
                ['escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
