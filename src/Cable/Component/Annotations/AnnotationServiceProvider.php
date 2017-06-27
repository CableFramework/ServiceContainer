<?php

namespace Cable\Container\Annotations;


use Cable\Annotation\Annotation;
use Cable\Annotation\Parser;
use Cable\Container\ServiceProvider;
use function foo\func;

class AnnotationServiceProvider extends ServiceProvider
{

    /**
     * register new providers or something
     *
     * @return mixed
     */
    public function boot(){}

    /**
     * register the content
     *
     * @return mixed
     */
    public function register()
    {
        $this->getContainer()->add(Annotation::class, function() {
            Annotation::setContainer($this->getContainer());

            return new Annotation((new Parser())->skipPhpDoc());
        });
    }
}