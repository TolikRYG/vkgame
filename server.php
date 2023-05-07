<?php
// Подключаемся к API VK.com
require_once 'vk.php';
$vk = new VK\Client\VKApiClient();

// Подключаемся к базе данных MySQL с помощью PDO
$dsn = 'mysql:host=localhost;dbname=game_db;charset=utf8';
$user = 'root';
$password = '';
try {
  $pdo = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
  die('Connection failed: ' . $e->getMessage());
}

// Создаем класс Player для хранения данных об игроках
class Player {
  public $id; // ID игрока в VK.com
  public $name; // Имя игрока
  public $photo; // URL фото игрока
  public $level; // Уровень игрока
  public $hp; // Здоровье игрока
  public $coins; // Монеты игрока
  public $items; // Массив предметов игрока
  public $online; // Статус онлайн игрока

  // Конструктор класса Player
  public function __construct($id) {
    global $pdo, $vk;
    // Получаем данные об игроке из базы данных
    $stmt = $pdo->prepare('SELECT * FROM players WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
      // Если игрок уже есть в базе данных, заполняем его свойства из таблицы
      $this->id = $row['id'];
      $this->name = $row['name'];
      $this->photo = $row['photo'];
      $this->level = $row['level'];
      $this->hp = $row['hp'];
      $this->coins = $row['coins'];
      // Декодируем JSON-строку с предметами в массив
      $this->items = json_decode($row['items'], true);
    } else {
      // Если игрока нет в базе данных, получаем его данные из API VK.com и добавляем его в таблицу
      try {
        // Получаем имя и фото игрока из API VK.com
        $user = $vk->users()->get('YOUR_ACCESS_TOKEN', [
          'user_ids' => [$id],
          'fields' => ['photo_100']
        ])[0];
        $this->id = $id;
        $this->name = $user['first_name'] . ' ' . $user['last_name'];
        $this->photo = $user['photo_100'];
        // Задаем начальные значения уровня, здоровья и монет
        $this->level = 1;
        $this->hp = 100;
        $this->coins = 0;
        // Создаем пустой массив предметов
        $this->items = [];
        // Кодируем массив предметов в JSON-строку для хранения в базе данных
        $items_json = json_encode($this->items);
        // Добавляем игрока в таблицу players
        $stmt = $pdo->prepare('INSERT INTO players (id, name, photo, level, hp, coins, items) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$this->id, $this->name, $this->photo, $this->level, $this->hp, $this->coins, $items_json]);
      } catch (VK\Exceptions\VKApiException $e) {
        // Если произошла ошибка при обращении к API VK.com, выводим сообщение об ошибке
        die('VK API error: ' . $e->getMessage());
      }
    }
    // Получаем статус онлайн игрока из API VK.com
    try {
      $online = $vk->users()->get('YOUR_ACCESS_TOKEN', [
        'user_ids' => [$this->id],
        'fields' => ['online']
      ])[0]['online'];
      $this->online = ($online == 1) ? true : false;
    } catch (VK\Exceptions\VKApiException $e) {
      // Если произошла ошибка при обращении к API VK.com, выводим сообщение об ошибке
      die('VK API error: ' . $e->getMessage());
    }
  }

  // Метод для обновления данных игрока в базе данных
  public function update() {
    global $pdo;
    // Кодируем массив предметов в JSON-строку для хранения в базе данных
    $items_json = json_encode($this->items);
    // Обновляем данные игрока в таблице players
    $stmt = $pdo->prepare('UPDATE players SET name = ?, photo = ?, level = ?, hp = ?, coins = ?, items = ? WHERE id = ?');
    $stmt->execute([$this->name, $this->photo, $this->level, $this->hp, $this->coins, $items_json, $this->id]);
  }

  // Метод для получения данных друзей игрока из API VK.com и базы данных
  public function getFriends() {
    global $pdo, $vk;
    // Создаем пустой массив для хранения друзей игрока
    $friends = [];
    try {
      // Получаем ID друзей игрока из API VK.com
      $friends_ids = $vk->friends()->get('YOUR_ACCESS_TOKEN', [
        'user_id' => $this->id,
        'order' => 'hints'
      ])['items'];
      // Для каждого ID друга игрока
      foreach ($friends_ids as $friend_id) {
        // Создаем объект класса Player для друга игрока
        $friend = new Player($friend_id);
        // Добавляем объект друга в массив друзей
        array_push($friends, $friend);
      }
      // Возвращаем массив друзей
      return $friends;
    } catch (VK\Exceptions\VKApiException $e) {
      // Если произошла ошибка при обращении к API VK.com, выводим сообщение об ошибке
      die('VK API error: ' . $e->getMessage());
    }
  }

  // Метод для отправки приглашения в игру другу игрока с помощью API VK.com
  public function inviteFriend($friend_id) {
    global $vk;
    try {
      // Отправляем уведомление другу игрока с текстом приглашения в игру и ссылкой на приложение
      $vk->notifications()->sendMessage('YOUR_ACCESS_TOKEN', [
        'user_ids' => [$friend_id],
        'message' => 'Привет! Я приглашаю тебя поиграть со мной в три в ряд! Это очень интересная и захватывающая игра! Присоединяйся по ссылке: https://vk.com/app123456',
        'fragment' => 'invite'
      ]);
    } catch (VK\Exceptions\VKApiException $e) {
      // Если произошла ошибка при обращении к API VK.com, выводим сообщение об ошибке
      die('VK API error: ' . $e->getMessage());
    }
  }
}

// Создаем класс Game для хранения данных об играх
class Game {
  public $id; // ID игры в базе данных
  public $player1; // Объект класса Player для первого игрока
  public $player2; // Объект класса Player для второго игрока
  public $turn; // ID игрока, чей сейчас ход
  public $board; // Двумерный массив для хранения состояния игрового поля
  public $status; // Статус игры: pending - ожидание второго игрока, active - игра активна, finished - игра завершена

  // Конструктор класса Game
  public function __construct($id) {
    global $pdo;
    // Получаем данные об игре из базы данных
    $stmt = $pdo->prepare('SELECT * FROM games WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
      // Если игра уже есть в базе данных, заполняем ее свойства из таблицы
      $this->id = $row['id'];
      // Создаем объекты класса Player для обоих игроков
      $this->player1 = new Player($row['player1']);
      $this->player2 = new Player($row['player2']);
      $this->turn = $row['turn'];
      // Декодируем JSON-строку с состоянием игрового поля в двумерный массив
      $this->board = json_decode($row['board'], true);
      $this->status = $row['status'];
    } else {
      // Если игры нет в базе данных, создаем новую игру с заданным ID первого игрока и добавляем ее в таблицу
      // Создаем объект класса Player для первого игрока
      $this->player1 = new Player($id);
      // Задаем пустое значение для второго игрока
      $this->player2 = null;
      // Задаем ход первого игрока
      $this->turn = $id;
      // Создаем пустой двумерный массив для хранения состояния игрового поля
      $this->board = [];
      for ($i = 0; $i < 8; $i++) {
        for ($j = 0; $j < 8; $j++) {
          // Заполняем каждую ячейку массива случайным цветом от 1 до 6
          $this->board[$i][$j] = rand(1, 6);
        }
      }
      // Задаем статус ожидания второго игрока
      $this->status = 'pending';
      // Кодируем двумерный массив с состоянием игрового поля в JSON-строку для хранения в базе данных
      $board_json = json_encode($this->board);
      // Добавляем игру в таблицу games
      $stmt = $pdo->prepare('INSERT INTO games (id, player1, player2, turn, board, status) VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute([$this->id, $this->player1->id, null, $this->turn, $board_json, $this->status]);
    }
  }

  // Метод для обновления данных игры в базе данных
  public function update() {
    global $pdo;
    // Кодируем двумерный массив с состоянием игрового поля в JSON-строку для хранения в базе данных
    $board_json = json_encode($this->board);
    // Обновляем данные игры в таблице games
    $stmt = $pdo->prepare('UPDATE games SET player1 = ?, player2 = ?, turn = ?, board = ?, status = ? WHERE id = ?');
    $stmt->execute([$this->player1->id, $this->player2->id, $this->turn, $board_json, $this->status, $this->id]);
  }

  // Метод для добавления второго игрока в игру
  public function join($id) {
    // Проверяем, что игра еще не началась и что ID второго игрока не совпадает с ID первого игрока
    if ($this->status == 'pending' && $id != $this->player1->id) {
      // Создаем объект класса Player для второго игрока
      $this->player2 = new Player($id);
      // Меняем статус игры на активный
      $this->status = 'active';
      // Обновляем данные игры в базе данных
      $this->update();
      // Отправляем уведомление первому игроку о том, что второй игрок присоединился к игре
      $this->player1->inviteFriend($this->player2->id);
    }
  }

  // Метод для проверки наличия комбинации из трех или более одинаковых цветов на игровом поле
  public function checkMatch() {
    // Создаем пустой массив для хранения координат ячеек с комбинацией
    $match = [];
    // Проверяем каждую ячейку игрового поля по горизонтали и вертикали
    for ($i = 0; $i < 8; $i++) {
      for ($j = 0; $j < 8; $j++) {
        // Получаем цвет текущей ячейки
        $color = $this->board[$i][$j];
        // Создаем переменные для хранения количества одинаковых цветов подряд по горизонтали и вертикали
        $horizontal = 1;
        $vertical = 1;
        // Проверяем ячейки справа от текущей ячейки на совпадение цвета
        for ($k = $j + 1; $k < 8; $k++) {
          if ($this->board[$i][$k] == $color) {
            // Если цвет совпадает, увеличиваем количество одинаковых цветов по горизонтали на единицу
            $horizontal++;
          } else {
            // Если цвет не совпадает, прерываем цикл
            break;
          }
        }
        // Проверяем ячейки снизу от текущей ячейки на совпадение цвета
        for ($k = $i + 1; $k < 8; $k++) {
          if ($this->board[$k][$j] == $color) {
            // Если цвет совпадает, увеличиваем количество одинаковых цветов по вертикали на единицу
            $vertical++;
          } else {
            // Если цвет не совпадает, прерываем цикл
            break;
          }
        }
        // Проверяем, что количество одинаковых цветов по горизонтали или по вертикали больше или равно трех
        if ($horizontal >= 3 || $vertical >= 3) {
          // Добавляем координаты текущей ячейки в массив с комбинацией
          array_push($match, [$i, $j]);
        }
      }
    }
    // Возвращаем массив с комбинацией или null, если комбинации нет
    return (count($match) > 0) ? $match : null;
  }

  // Метод для обработки хода игрока
  public function makeMove($player_id, $x1, $y1, $x2, $y2) {
    // Проверяем, что игра активна и что ход принадлежит данному игроку
    if ($this->status == 'active' && $this->turn == $player_id) {
      // Проверяем, что координаты ячеек для обмена находятся в пределах игрового поля и что они соседние
      if ($x1 >= 0 && $x1 < 8 && $y1 >= 0 && $y1 < 8 && $x2 >= 0 && $x2 < 8 && $y2 >= 0 && $y2 < 8 && abs($x1 - $x2) + abs($y1 - $y2) == 1) {
        // Обмениваем значения ячеек местами
        $temp = $this->board[$x1][$y1];
        $this->board[$x1][$y1] = $this->board[$x2][$y2];
        $this->board[$x2][$y2] = $temp;
        // Проверяем наличие комбинации на игровом поле после обмена
        $match = $this->checkMatch();
        if ($match) {
          // Если есть комбинация, удаляем ячейки с комбинацией и заполняем пустые места новыми случайными цветами
          foreach ($match as $cell) {
            // Получаем координаты ячейки с комбинацией
            $i = $cell[0];
            $j = $cell[1];
            // Сдвигаем все ячейки над текущей ячейкой на одну позицию вниз
            for ($k = $i; $k > 0; $k--) {
              $this->board[$k][$j] = $this->board[$k - 1][$j];
            }
            // Заполняем верхнюю ячейку случайным цветом от 1 до 6
            $this->board[0][$j] = rand(1, 6);
          }
          // Вычисляем урон, который наносит игрок своему сопернику в зависимости от количества ячеек в комбинации и уровня игрока
          $damage = count($match) * (1 + ($this->turn == $this->player1->id) ? $this->player1->level : $this->player2->level);
          // Уменьшаем здоровье соперника на величину урона
          if ($this->turn == $this->player1->id) {
            $this->player2->hp -= $damage;
          } else {
            $this->player1->hp -= $damage;
          }
          // Проверяем, что здоровье соперника не опустилось ниже нуля
          if ($this->player2->hp <= 0) {
            // Если здоровье соперника нулевое или отрицательное, объявляем победителя и завершаем игру
            $this->status = 'finished';
            $winner = $this->player1;
            $loser = $this->player2;
          } elseif ($this->player1->hp <= 0) {
            // Если здоровье соперника нулевое или отрицательное, объявляем победителя и завершаем игру
            $this->status = 'finished';
            $winner = $this->player2;
            $loser = $this->player1;
          } else {
            // Если здоровье обоих игроков положительное, меняем ход на другого игрока
            $this->turn = ($this->turn == $this->player1->id) ? $this->player2->id : $this->player1->id;
          }
          // Обновляем данные игры и игроков в базе данных
          $this->update();
          $this->player1->update();
          $this->player2->update();
          // Возвращаем результат хода в виде ассоциативного массива
          return [
            'success' => true,
            'match' => $match,
            'damage' => $damage,
            'hp1' => $this->player1->hp,
            'hp2' => $this->player2->hp,
            'turn' => $this->turn,
            'status' => $this->status,
            'winner' => isset($winner) ? $winner : null,
            'loser' => isset($loser) ? $loser : null
          ];
        } else {
          // Если нет комбинации, возвращаем ячейки на исходные места
          $temp = $this->board[$x1][$y1];
          $this->board[$x1][$y1] = $this->board[$x2][$y2];
          $this->board[$x2][$y2] = $temp;
          // Возвращаем результат хода в виде ассоциативного массива
          return ['success' => false,
            'match' => null,
            'damage' => 0,
            'hp1' => $this->player1->hp,
            'hp2' => $this->player2->hp,
            'turn' => $this->turn,
            'status' => $this->status,
            'winner' => null,
            'loser' => null
          ];
        }
      } else {
        // Если координаты ячеек для обмена невалидные, возвращаем результат хода в виде ассоциативного массива
        return [
          'success' => false,
          'match' => null,
          'damage' => 0,
          'hp1' => $this->player1->hp,
          'hp2' => $this->player2->hp,
          'turn' => $this->turn,
          'status' => $this->status,
          'winner' => null,
          'loser' => null
        ];
      }
    } else {
      // Если игра неактивна или ход не принадлежит данному игроку, возвращаем результат хода в виде ассоциативного массива
      return [
        'success' => false,
        'match' => null,
        'damage' => 0,
        'hp1' => $this->player1->hp,
        'hp2' => $this->player2->hp,
        'turn' => $this->turn,
        'status' => $this->status,
        'winner' => null,
        'loser' => null
      ];
    }
  }

  // Метод для покупки предмета игроком
  public function buyItem($player_id, $item_id) {
    // Проверяем, что игра активна и что ход принадлежит данному игроку
    if ($this->status == 'active' && $this->turn == $player_id) {
      // Получаем объект класса Player для данного игрока
      $player = ($this->turn == $this->player1->id) ? $this->player1 : $this->player2;
      // Проверяем, что ID предмета находится в пределах от 1 до 6
      if ($item_id >= 1 && $item_id <= 6) {
        // Создаем ассоциативный массив с ценами и эффектами предметов
        $items = [
          1 => ['price' => 10, 'effect' => '+10 HP'], // Лечебное зелье
          2 => ['price' => 20, 'effect' => '+1 Level'], // Магический амулет
          3 => ['price' => 30, 'effect' => '+5 Damage'], // Острый меч
          4 => ['price' => 40, 'effect' => '+10 Damage'], // Пламенный топор
          5 => ['price' => 50, 'effect' => '+15 Damage'], // Ледяной посох
          6 => ['price' => 60, 'effect' => '+20 Damage'] // Молниеносный лук
        ];
        // Получаем цену и эффект выбранного предмета
        $price = $items[$item_id]['price'];
        $effect = $items[$item_id]['effect'];
        // Проверяем, что у игрока достаточно монет для покупки предмета
        if ($player->coins >= $price) {
          // Уменьшаем количество монет игрока на цену предмета
          $player->coins -= $price;
          // Добавляем ID предмета в массив предметов игрока
          array_push($player->items, $item_id);
          // Применяем эффект предмета к игроку в зависимости от типа предмета
          switch ($item_id) {
            case 1: // Лечебное зелье
              // Увеличиваем здоровье игрока на 10 единиц
              $player->hp += 10;
              break;
            case 2: // Магический амулет
              // Увеличиваем уровень игрока на 1 единицу
              $player->level += 1;
              break;
            case 3: // Острый меч
              // Увеличиваем урон игрока на 5 единиц
              $player->damage += 5;
              break;
            case 4: // Пламенный топор
              // Увеличиваем урон игрока на 10 единиц
              $player->damage += 10;
              break;
            case 5: // Ледяной посох
              // Увеличиваем урон игрока на 15 единиц
              $player->damage += 15;
              break;
            case 6: // Молниеносный лук
              // Увеличиваем урон игрока на 20 единиц
              $player->damage += 20;
              break;
          }
          // Обновляем данные игрока в базе данных
          $player->update();
          // Возвращаем результат покупки в виде ассоциативного массива
          return ['success' => true,
            'item_id' => $item_id,
            'price' => $price,
            'effect' => $effect,
            'coins' => $player->coins,
            'hp' => $player->hp,
            'level' => $player->level,
            'damage' => $player->damage
          ];
        } else {
          // Если у игрока недостаточно монет для покупки предмета, возвращаем результат покупки в виде ассоциативного массива
          return [
            'success' => false,
            'item_id' => $item_id,
            'price' => $price,
            'effect' => null,
            'coins' => $player->coins,
            'hp' => $player->hp,
            'level' => $player->level,
            'damage' => $player->damage
          ];
        }
      } else {
        // Если ID предмета невалидный, возвращаем результат покупки в виде ассоциативного массива
        return [
          'success' => false,
          'item_id' => $item_id,
          'price' => null,
          'effect' => null,
          'coins' => $player->coins,
          'hp' => $player->hp,
          'level' => $player->level,
          'damage' => $player->damage
        ];
      }
    } else {
      // Если игра неактивна или ход не принадлежит данному игроку, возвращаем результат покупки в виде ассоциативного массива
      return [
        'success' => false,
        'item_id' => null,
        'price' => null,
        'effect' => null,
        'coins' => $player->coins,
        'hp' => $player->hp,
        'level' => $player->level,
        'damage' => $player->damage
      ];
    }
  }

  // Метод для получения монет за просмотр видео рекламы
  public function watchAd($player_id) {
    // Проверяем, что игра активна и что ход принадлежит данному игроку
    if ($this->status == 'active' && $this->turn == $player_id) {
      // Получаем объект класса Player для данного игрока
      $player = ($this->turn == $this->player1->id) ? $this->player1 : $this->player2;
      // Задаем количество монет, которые получает игрок за просмотр видео рекламы
      $coins = 5;
      // Увеличиваем количество монет игрока на заданное значение
      $player->coins += $coins;
      // Обновляем данные игрока в базе данных
      $player->update();
      // Возвращаем результат просмотра видео рекламы в виде ассоциативного массива
      return [
        'success' => true,
        'coins' => $coins,
        'total_coins' => $player->coins
      ];
    } else {
      // Если игра неактивна или ход не принадлежит данному игроку, возвращаем результат просмотра видео рекламы в виде ассоциативного массива
      return [
        'success' => false,
        'coins' => null,
        'total_coins' => null
      ];
    }
  }
}
?>