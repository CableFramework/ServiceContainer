# ServiceContainer
CableFramework service container

##  Create new instance

```php
$container = \Cable\Container\Factory::create();
```

## Add a new service

```php

// you can give an anonymous function
$container->add('test', function(){
  return new Test();
});

// you can give the name of class

$container->add('test', Test::class);


// you can give the already exists class instance

$container->add('test', new Test());

```

## Resolve the service

```php

$test = $container->resolve('test');

// you can give arguments,
// they will be used in constructor calling,
// of course if you didnt give an instance 


$test->resolve('test', array('message' => 'hello world'));

```

## Expectations

```php

//you may want to check resolved value is an instance of something
// well you can to that like that

$resolved= $container->resolve('test');

if(!$resolved instanceof MyTestInterface){

}

// the problem that you may want to check multipile interfaces
// well, you can do that like that

try{

// you can always give an array like, expect('test', [MyInterface::class, MySecondInterface::class]);
$container->expect('test', MyTestInterface::class);
}catch(ExpectationException $e){
  echo "give me something valid";
}
// if test doesnot return an instanceof MyTestInterface
// container will throw an expectation exception


```

## ServiceProviders

```php

class Provider extends ServiceProvider{
   public function register(){}
   public function boot(){
     $this->getContainer()->add('test', Test::class);
   }
}

$container = \Cable\Container\Factory::create();

$container->addProvider(Provider::class);


// now you can resolve the 'test' service

$test = $container->resolve('test');

```

## tag


```php 

$container->tag([Deneme::class, Test::class], 'test');


list($deneme, $test) = $container->tagged('test');

```

## Using Annotations


#### Provider Annotation

You can put a @Provider annotation into class comment doc

@Provider will be trigged when you are trying to resolve that class

```php 

/**
 *
 * @Provider("Your\Provide\Class")
 * // you can give multiple providers like @Provider({"FirstProviderClass", "SecondProviderClass"})
 */
class TestClass{


}

```

#### Inject Annotation

You can put @Inject Annotation into any method

```php
use Cable\Container\Annotations\Inject;

$container->add("my_alias_to_resolve", function(){
      return "hello world";
});


class TestClass{
    
    
    /**
     *
     * @Inject({"$test": "my_alias_to_resolve"})
     *
     */
    public function __construct($test){
    
    }
    
}

 ```

Same as ;

```php 

$container->when(Test::class)
          ->needs("test")
          ->give(function(){
              return $this->getContainer()->make("my_alias_to_resolve");
          });

```