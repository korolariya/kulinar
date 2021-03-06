<?php
class Recipes extends auth
{
    
    public $recipes;
    public $structure;
    
    /**
     * Получение списков
     * @return Ингридиенты и граммы
     */
    function getAllIngredients() {
        $this->sql = "SELECT *  FROM ingredients i " . " WHERE i.id>0 " . "ORDER BY i.name ASC";
        $this->query();
        $ingredients = $this->LoadObjectList();
        
        $this->sql = "SELECT *  FROM units u WHERE u.id>0 ORDER BY u.name ASC";
        $this->query();
        $units = $this->LoadObjectList();
        
        $answer = new stdClass();
        $answer->ingredients = $ingredients;
        $answer->units = $units;
        return json_encode($answer);
    }
    
    function getIngredients($rec_id) {
        
        $this->sql = "SELECT *  FROM recipes " . "WHERE alias='$rec_id'";
        $this->query();
        $rec = $this->LoadObject();
        
        $fild = ' i.name,
  u.name mer,
  u.shortcut,
  s.count';
        
        $this->sql = "SELECT $fild  FROM structure s " . " LEFT JOIN ingredients i ON i.id = s.ingredients_id " . " LEFT JOIN units u  ON u.id = s.units_id" . " WHERE s.recipes_id = $rec->id";
        $this->query();
        $this->structure = $this->LoadObjectList();
        foreach ($this->structure as $key => $value) {
            if ($value->count == 0) {
                $value->count = '';
            }
        }
        $rec->structure = $this->structure;
        
        return json_encode($rec);
    }
    
    function getIngredientsById($rec_id) {
        $fild = ' i.name,
  u.name mer,
  u.shortcut,
  s.count';
        
        $this->sql = "SELECT $fild  FROM structure s " . " LEFT JOIN ingredients i ON i.id = s.ingredients_id " . " LEFT JOIN units u  ON u.id = s.units_id" . " WHERE s.recipes_id = $rec_id";
        $this->query();
        $this->structure = $this->LoadObjectList();
        if ($this->structure) foreach ($this->structure as $key => $value) {
            if ($value->count == 0) {
                $value->count = '';
            }
        }
        
        return $this->structure;
    }
    
    function getRecipes($shag) {
        $shag*= 20;
        $this->sql = "SELECT recipes.*,  photos.path FROM recipes " . "LEFT JOIN photos  ON photos.rec_id = recipes.id" . " ORDER BY recipes.id DESC LIMIT $shag,20";
        
        // die();
        
        $this->query();
        $this->recipes = $this->LoadObjectList();
        if ($this->recipes) {
            foreach ($this->recipes as $key => $value) {
                $value->ingredients = $this->getIngredientsById($value->id);
            }
        } else {
            $answer = array(
                'msg' => 'end'
            );
            return json_encode($answer);
        }
        
        return json_encode($this->recipes);
    }
    
    // Транслитерация строк.
    function rus2translit($string) {
        $converter = array(
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ь' => '\'',
            'ы' => 'y',
            'ъ' => '\'',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'E',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Sch',
            'Ь' => '\'',
            'Ы' => 'Y',
            'Ъ' => '\'',
            'Э' => 'E',
            'Ю' => 'Yu',
            'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }
    
    function str2url($str) {
        
        // переводим в транслит
        $str = $this->rus2translit($str);
        
        // в нижний регистр
        $str = strtolower($str);
        
        // заменям все ненужное нам на "-"
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
        
        // удаляем начальные и конечные '-'
        $str = trim($str, "-");
        return $str;
    }
    
    function get_categories() {
        $this->sql = "SELECT *  FROM categories ";
        $this->query();
        $result = json_encode($this->LoadObjectList());
        return $result;
    }
    
    function add($post) {
        
        //Тут еще нужна проверка прав пользователя
        //file_put_contents('test.txt', $_POST);
        $recept = json_decode($post['recept']);
        
        $user = json_decode($this->get_user_in());
        
        $insert_arr = array(
            'name' => $recept->name,
            'user_id' => $user->user_id,
            'process' => $recept->htmlcontent,
            'alias' => $this->str2url($recept->name),
            'cat_id' => (int)$recept->cat->id
        );
        $result = $this->mysql_insert('recipes', $insert_arr);
        
        if (!$result) {
            return array(
                'msg' => 'Произошла ошибка, рецепт не записался в базу',
                status => 0,
                'query' => $this->query,
                'log' => $this->log
            );
        }
        
        $rec_id = $this->last_id;
        
        $ingredients = json_decode($this->getAllIngredients());
        $ingredients = $ingredients->ingredients;
        
        //Фото
        $updates = array(
            'rec_id' => $rec_id
        );
        $where = array(
            'id' => $recept->main_photo->id
        );
        $this->mysql_update('photos', $updates, $where);

        //Ингридиенты
        
        foreach ($recept->multipleIngredients->items as $k => $v) {
            $bul = true;
             //Предпологаем что нужно записать в таблицу
            foreach ($ingredients as $key => $value) {
                if ($value->name == $v->name) {
                    $bul = false;
                     //Ищем совпадения если нашли выключаем запись и выходим из цикла
                    break;
                }
            }
            
            if ($bul) {
                $insert_arr = array(
                    'name' => $v->name
                );
                $result = $this->mysql_insert('ingredients', $insert_arr);
                
                // $ingredient_id = $this->last_id;
                $v->id = $this->last_id;
            }
        }
        
        //    file_put_contents('test.txt', json_encode($recept->multipleIngredients->items));
        
        foreach ($recept->multipleIngredients->items as $key => $value) {
            $insert_arr = array(
                'recipes_id' => $rec_id,
                'ingredients_id' => $value->id,
                'units_id' => $value->unit->selected->id,
                'count' => $value->count
            );
            $result = $this->mysql_insert('structure', $insert_arr);
            if (!$result) {
                return array(
                    'msg' => 'Произошла ошибка, ингредиент не записался в базу',
                    status => 0
                );
            }
        }
        return array(
            'msg' => 'Рецепт успешно добавлен',
            status => 1
        );
    }
}
?>
