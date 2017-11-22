<?php

namespace Arcana\AnalyticBundle\Twig;

use Arcana\AnalyticBundle\Code\AnalyticCode;
use \Twig_Extension;
use \Twig_SimpleFunction;

class AnalyticExtension extends Twig_Extension
{
    /**
     * @var AnalyticCode
     */
    private $code;

    public function __construct(AnalyticCode $code)
    {
        $this->code = $code;
    }

    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('arcana_analytic', array($this, 'analyticFunction')),
        );
    }

    public function analyticFunction()
    {
        return $this->code->generateAnalyticCode();
    }

    public function getName()
    {
        return 'arcana_analytic_extension';
    }
}
