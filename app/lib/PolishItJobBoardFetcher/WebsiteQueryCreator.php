<?php
namespace PolishItJobBoardFetcher;

use Exception;
use ReflectionClass;

use PolishItJobBoardFetcher\DataProvider\WebsiteInterface;

/**
 * Query class
 */
class WebsiteQueryCreator implements QueryCreatorInterface
{
    /**
     * @var array
     */
    public $query;

    private $avaliableQueryVariables = [
      "PolishItJobBoardFetcher\DataProvider\Fields\TechnologyQueryFieldInterface" => "technology",
      "PolishItJobBoardFetcher\DataProvider\Fields\CityQueryFieldInterface" => "city",
      "PolishItJobBoardFetcher\DataProvider\Fields\ExperienceQueryFieldInterface" => "experience",
      "PolishItJobBoardFetcher\DataProvider\Fields\CategoryQueryFieldInterface" => "category",
      "PolishItJobBoardFetcher\DataProvider\Fields\ContractTypeQueryFieldInterface" => "contract_type",
      "PolishItJobBoardFetcher\DataProvider\Fields\SalaryQueryFieldInterface" => "salary"
    ];

    public function __construct(array $query)
    {
        $this->query = $query;
    }

    public function getQueryForClass(WebsiteInterface $class_instance) : array
    {
        $reflection = new ReflectionClass($class_instance);
        $interfaces = $reflection->getInterfaces();
        $query = [];

        foreach ($this->avaliableQueryVariables as $class => $value) {
            if (array_key_exists($class, $interfaces)) {
                $interface_methods = $interfaces[$class]->getMethods();

                $methods = [
                  "has" => null,
                  "allows" => null,
                  "get" => null
                ];

                foreach ($interface_methods as $key => $reflectionMethod) {
                    $name = $reflectionMethod->name;
                    if (strpos($name, "has") !== false) {
                        $methods["has"] = $name;
                    } elseif (strpos($name, "allows") !== false) {
                        $methods["allows"] = $name;
                    } elseif (strpos($name, "get") !== false) {
                        $methods["get"] = $name;
                    } else {
                        throw new Exception("None of the required methods for the interface found");
                    }
                }

                if (empty(array_filter($methods)) or count($methods) !== 3) {
                    throw new Exception("Interface ".$interfaces[$class]->name." is missing required methods");
                }


                if (isset($this->query[$value])) {
                    $query_field = $this->query[$value];
                    if (!is_null($query_field) and (!$class_instance->{$methods["allows"]}() or $class_instance->{$methods["has"]}($query_field))) {
                        $query[$value] = $this->getAdaptedNameFromArray($class_instance->{$methods["get"]}(), $query_field);
                    } else {
                        $query[$value] = $query_field;
                    }
                } else {
                    $query[$value] = null;
                }
            }
        }

        return $query;
    }

    public function addQueryVariable(string $class_interface, string $variable_name)
    {
        if (class_exists($class_interface)) {
            $this->avaliableQueryVariables[$class_interface] = $variable_name;
        } else {
            throw new Exception("Class $class_interface not found");
        }
    }

    /**
     * Checks if the value is inside the $array, if it is it will look for the $look_for inside key/values
     * and if it is inside values it will return the key
     * @param  array  $array    ["key" => ["value", "value"] ... ]
     * @param  string $look_for The value to look for inside the $array
     * @return string|null          If it finds something it will return the value else null.
     */
    protected function getAdaptedNameFromArray(array $array, string $look_for) : ?string
    {
        foreach ($array as $key => $category_depth1) {
            if (is_array($category_depth1)) {
                if ($key === $look_for) {
                    return $key;
                }
                foreach ($category_depth1 as $category_depth2) {
                    if ($look_for === $category_depth2) {
                        return $key;
                    }
                }
            } elseif ($category_depth1 === $look_for) {
                return $look_for;
            } elseif (is_string($category_depth1) && $category_depth1 === $look_for) {
                return $category_depth1;
            }
        }

        return null;
    }
}
