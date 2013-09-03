<?php

namespace Barryvdh\TwigBridge;

use Twig_Template;


/**
 * Default base class for compiled templates.
 */
abstract class Template extends Twig_Template
{

    protected function getViewName(){
        $name = $this->getTemplateName();
        if(\Str::endsWith($name, '.twig')){
            $name = substr($name, 0, -5);
        }
        $name = str_replace(DIRECTORY_SEPARATOR, '.', $name);
        return $name;

    }
    public function displayBlock($name, array $context, array $blocks = array())
    {
        $env  = $context['__env'];

        \View::callCreator($view = new \Illuminate\View\View($env, $env->getEngineResolver()->resolve('twig'), $this->getViewName(), null, $context));

        \View::callComposer($view);

        $context = $view->getData();

        parent::displayBlock($name, $context, $blocks);
    }

    protected function getAttribute($object, $item, array $arguments = array(), $type = Twig_Template::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false){

        $mutator = "get".studly_case($item).'Attribute';
        if(
            Twig_Template::METHOD_CALL !== $type //Don't handle Method Calls
            and $object instanceof \Illuminate\Database\Eloquent\Model //Only handle Models
            and (
                isset($object->{$item})     //Normal attribute
                or method_exists($object, $mutator)     //getMutator
                or method_exists($object, $item)    //Relation
            )){
            $value = $object->{$item};
        }else{
            $value = parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
        }


        return $value;

    }


}