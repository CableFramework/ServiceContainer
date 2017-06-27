<?php

namespace Cable\Container\Annotations;


use Cable\Annotation\Annotation;
use Cable\Annotation\Parser;
use Cable\Container\ReflectionException;
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
     * @throws Parser\Exception\ParserException
     * @throws \ReflectionException
     * @return mixed
     */
    public function register()
    {
        $this->getContainer()->singleton(Annotation::class, function() {
            Annotation::setContainer($this->getContainer());

            $annotation =  new Annotation((new Parser())->skipPhpDoc());

            return $annotation->addCommand(new Inject())
                ->addCommand(new Provider());
        });
    }
}