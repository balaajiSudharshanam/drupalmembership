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

/* modules/contrib/commerce_license/templates/commerce-license-expire.html.twig */
class __TwigTemplate_86415daa4ddc5af88f32b4e8ba6c6294 extends Template
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
        // line 14
        echo "<table style=\"margin: 15px auto 0 auto; max-width: 768px; font-family: arial,sans-serif\">
  <tbody>
  <tr>
    <td>
      ";
        // line 31
        echo "      <table style=\"text-align: center; min-width: 450px; margin: 5px auto 0 auto; border: 1px solid #cccccc; border-radius: 5px; padding: 40px 30px 30px 30px;\">
        <tbody>
        <tr>
          <td style=\"font-size: 30px; padding-bottom: 30px\">";
        // line 34
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("License Expiry"));
        echo "</td>
        </tr>
        <tr>
          <td style=\"font-weight: bold; padding-top:15px; padding-bottom: 15px; text-align: left; border-top: 1px solid #cccccc; border-bottom: 1px solid #cccccc\">
            ";
        // line 38
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Your purchase of @license has now expired.", ["@license" => twig_get_attribute($this->env, $this->source, ($context["license_entity"] ?? null), "label", [], "any", false, false, true, 38)]));
        echo "

            ";
        // line 40
        if (($context["purchased_entity"] ?? null)) {
            // line 41
            echo "              ";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("You may renew it by repurchasing <a href=\"@product-url\">@product-label</a>.", ["@product-url" => twig_get_attribute($this->env, $this->source,             // line 42
($context["purchased_entity_url"] ?? null), "toString", [], "any", false, false, true, 42), "@product-label" => twig_get_attribute($this->env, $this->source,             // line 43
($context["purchased_entity"] ?? null), "label", [], "any", false, false, true, 43)]));
            // line 44
            echo "
            ";
        }
        // line 46
        echo "          </td>
        </tr>
        </tbody>
      </table>
    </td>
  </tr>
  </tbody>
</table>
";
    }

    public function getTemplateName()
    {
        return "modules/contrib/commerce_license/templates/commerce-license-expire.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  73 => 46,  69 => 44,  67 => 43,  66 => 42,  64 => 41,  62 => 40,  57 => 38,  50 => 34,  45 => 31,  39 => 14,);
    }

    public function getSourceContext()
    {
        return new Source("{#
/**
 * @file
 * Template for the license expiry mail.
 *
 * Available variables:
 * - license_entity: The license entity.
 * - purchased_entity: The entity which was purchased to obtain this license.
 * - purchased_entity_url: The URL of the purchased entity.
 *
 * @ingroup themeable
 */
#}
<table style=\"margin: 15px auto 0 auto; max-width: 768px; font-family: arial,sans-serif\">
  <tbody>
  <tr>
    <td>
      {#
      @todo restore this when the store is available from the license.
      See @todo in LicenseExpireNotify::process().
      <table style=\"margin-left: auto; margin-right: auto; max-width: 768px; text-align: center;\">
        <tbody>
        <tr>
          <td>
            <a href=\"{{ url('<front>') }}\" style=\"color: #0e69be; text-decoration: none; font-weight: bold; margin-top: 15px;\">{{ order_entity.getStore.label }}</a>
          </td>
        </tr>
        </tbody>
      </table>
      #}
      <table style=\"text-align: center; min-width: 450px; margin: 5px auto 0 auto; border: 1px solid #cccccc; border-radius: 5px; padding: 40px 30px 30px 30px;\">
        <tbody>
        <tr>
          <td style=\"font-size: 30px; padding-bottom: 30px\">{{ 'License Expiry'|t }}</td>
        </tr>
        <tr>
          <td style=\"font-weight: bold; padding-top:15px; padding-bottom: 15px; text-align: left; border-top: 1px solid #cccccc; border-bottom: 1px solid #cccccc\">
            {{ 'Your purchase of @license has now expired.'|t({'@license': license_entity.label}) }}

            {% if (purchased_entity) %}
              {{ 'You may renew it by repurchasing <a href=\"@product-url\">@product-label</a>.'|t({
                '@product-url': purchased_entity_url.toString,
                '@product-label': purchased_entity.label,
              }) }}
            {% endif %}
          </td>
        </tr>
        </tbody>
      </table>
    </td>
  </tr>
  </tbody>
</table>
", "modules/contrib/commerce_license/templates/commerce-license-expire.html.twig", "C:\\xampp\\htdocs\\membership\\web\\modules\\contrib\\commerce_license\\templates\\commerce-license-expire.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 40);
        static $filters = array("t" => 34);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['t'],
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
