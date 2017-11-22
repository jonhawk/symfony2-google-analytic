<?php

namespace Arcana\AnalyticBundle\Code;

use Symfony\Component\DependencyInjection\ContainerInterface;

class AnalyticCode
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param boolean $isEnabled
     */
    private $isEnabled = false;
    private $domain;
    private $domainCode;
    private $parameterPrefix = 'arcana_analytic.';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->isEnabled = $container->getParameter($this->parameterPrefix.'is_enabled');
        $this->domain = $container->getParameter($this->parameterPrefix.'domain');
        $this->domainCode = $container->getParameter($this->parameterPrefix.'domain_code');
    }

    public function generateAnalyticCode()
    {
        if (!$this->isEnabled) {
            return '';
        }

        return "<script>
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
          (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

          ga('create', '".$this->domainCode."', '".$this->domain."');
          ga('send', 'pageview');

        </script>";
    }
}
