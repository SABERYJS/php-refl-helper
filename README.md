# php-refl-helper
actually this library is a light PHP dependency injector
## why to use this library.
if you are a PHP developer, you will know that,PHP has a feature called *Reflection*,with that,you can do cool things,as  follows:
you can create  a Object dynamic,even there is some  dependences,this library will create it for you,not only but also,you can call any public method of it ,its dependences will also be parsed.

## how to use
first,we assume that there  are two class,code is here:
```php
class Apple{
  public function  shape(){
    return 'red';
  }
}

class Person{
  public function eat(Apple $apple){
    echo $apple->shape();
  }
}

class  Hunter{
    protected $weapon;
    public function __construct(Weapon $weapon)
    {
        $this->weapon=$weapon;
    }

    public function showWeapon(){
        $this->weapon->display();
    }

    private static function  getInstance(Weapon $weapon){
        return new self($weapon);
    }

    public static function create(Weapon $weapon){
        return new self($weapon);
    }
}

class  Weapon{
    private $name;
    private $price;
    private $type;
    private $level;
    public function __construct($name,$price,$type,$level)
    {
        $this->name=$name;
        $this->price=$price;
        $this->type=$price;
        $this->level=$level;
    }

    public function display(){
        echo 'weapon detail of'.$this->name.':'.$this->price.'-'.$this->type.':'.$this->level;
    }
}

```
as you see,there are two class called *Apple* and *Person*,
if you want to call eat method of *Person* ,you can do like this.
following code show how you can use this library.
```php
$defaultConfig=[
    'name'=>"saberyjs",
    'price'=>100,
    'type'=>'Melee',
    'level'=>4
];
$config=[
    'method'=>[
        'getInstance',
        'create'
    ],
    'factory'=>[
        Hunter::class=>function(){
            return new Hunter($routeHelper->get(Weapon::class));
        }
    ]
];
$reflHelper=new ReflHelper($config,$defaultConfig);
$reflHelper->callMethod($reflHelper->get(Apple::class),'eat');

$reflHelper->get(Hunter::class)->display();
```

as you see ,this library is very easy to use.totally,you can follows:
- call $reflHelper->get() can get a object
- call $reflHelper->callMethod() can call a method of a object
- call $reflHelper->callStaticMethod() can call static method 

if you look upper code carefully,you will find we pass two args($config,$defaultConfig) when we create *ReflHelper*,$config contain two main key(method,factory),
the method  key specify the method that will be called when library create instance,if class`__constructor not exist,$defaultConfig contain default value that will be used if it is a dependency of other

ReflHelper will do everything for you,even,you do not need to create Apple object,not only but also,this library also also support a function :
upper code will create instance of specified class,parse dependency

## contact 
*email*:saberyjs@gmail.com
*QQ*:1174332406
