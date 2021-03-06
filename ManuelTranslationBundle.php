<?php

namespace ManuelAguirre\Bundle\TranslationBundle;

use ManuelAguirre\Bundle\TranslationBundle\DependencyInjection\Compiler\AddTranslatorLoadersPass;
use ManuelAguirre\Bundle\TranslationBundle\DependencyInjection\Compiler\ConfigureExtractorsPass;
use ManuelAguirre\Bundle\TranslationBundle\DependencyInjection\Compiler\LoggingTranslatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ManuelTranslationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddTranslatorLoadersPass());
        $container->addCompilerPass(new ConfigureExtractorsPass());

//        if ($container->getParameter('kernel.debug')) {
//            $container->addCompilerPass(new LoggingTranslatorPass());
//        }
    }

}
