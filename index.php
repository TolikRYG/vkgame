<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Три в ряд</title>
  <!-- Подключаем стили CSS для оформления игрового интерфейса -->
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Создаем контейнер для игрового интерфейса -->
  <div id="game-container">
    <!-- Создаем элементы для отображения данных об игроках и игре -->
    <div id="player1-info">
      <img id="player1-photo" src="" alt="Фото первого игрока">
      <span id="player1-name">Имя первого игрока</span>
      <span id="player1-level">Уровень первого игрока</span>
      <span id="player1-hp">Здоровье первого игрока</span>
      <span id="player1-coins">Монеты первого игрока</span>
      <span id="player1-damage">Урон первого игрока</span>
      <div id="player1-items">Предметы первого игрока</div>
    </div>
    <div id="player2-info">
      <img id="player2-photo" src="" alt="Фото второго игрока">
      <span id="player2-name">Имя второго игрока</span>
      <span id="player2-level">Уровень второго игрока</span>
      <span id="player2-hp">Здоровье второго игрока</span>
      <span id="player2-coins">Монеты второго игрока</span>
      <span id="player2-damage">Урон второго игрока</span>
      <div id="player2-items">Предметы второго игрока</div>
    </div>
    <div id="game-info">
      <span id="game-turn">Ход: </span>
      <span id="game-status">Статус: </span>
      <span id="game-winner">Победитель: </span>
    </div>
    <!-- Создаем элемент для отображения игрового поля -->
    <div id="game-board"></div>
    <!-- Создаем элементы для отображения действий игрока -->
    <div id="game-actions">
      <button id="buy-item">Купить предмет</button>
      <button id="watch-ad">Посмотреть рекламу</button>
    </div>
    <!-- Создаем элементы для отображения друзей игрока и приглашения в игру -->
    <div id="game-friends">
      <span id="game-friends-title">Друзья:</span>
      <div id="game-friends-carousel"></div>
    </div>
  </div>
  <!-- Подключаем скрипт JavaScript для обработки логики игры и взаимодействия с сервером -->
  <script src="script.js"></script>
</body>
</html>