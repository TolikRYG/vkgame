// Создаем переменные для хранения элементов игрового интерфейса
const player1Photo = document.getElementById('player1-photo');
const player1Name = document.getElementById('player1-name');
const player1Level = document.getElementById('player1-level');
const player1Hp = document.getElementById('player1-hp');
const player1Coins = document.getElementById('player1-coins');
const player1Damage = document.getElementById('player1-damage');
const player1Items = document.getElementById('player1-items');

const player2Photo = document.getElementById('player2-photo');
const player2Name = document.getElementById('player2-name');
const player2Level = document.getElementById('player2-level');
const player2Hp = document.getElementById('player2-hp');
const player2Coins = document.getElementById('player2-coins');
const player2Damage = document.getElementById('player2-damage');
const player2Items = document.getElementById('player2-items');

const gameTurn = document.getElementById('game-turn');
const gameStatus = document.getElementById('game-status');
const gameWinner = document.getElementById('game-winner');

const gameBoard = document.getElementById('game-board');

const buyItem = document.getElementById('buy-item');
const watchAd = document.getElementById('watch-ad');

const gameFriendsTitle = document.getElementById('game-friends-title');
const gameFriendsCarousel = document.getElementById('game-friends-carousel');

// Создаем переменную для хранения ID текущего игрока
let playerId;

// Создаем переменную для хранения ID текущей игры
let gameId;

// Создаем переменную для хранения состояния игрового поля
let board;

// Создаем переменную для хранения координат первой ячейки для обмена
let firstCell;

// Создаем функцию для получения ID текущего игрока из URL-параметра
function getPlayerId() {
  // Получаем строку с URL-параметрами из адресной строки браузера
  const paramsString = window.location.search;
  // Создаем объект URLSearchParams для работы с URL-параметрами
  const params = new URLSearchParams(paramsString);
  // Возвращаем значение URL-параметра с именем id или null, если такого параметра нет
  return params.get('id') || null;
}

// Создаем функцию для получения ID текущей игры из URL-параметра
function getGameId() {
  // Получаем строку с URL-параметрами из адресной строки браузера
  const paramsString = window.location.search;
  // Создаем объект URLSearchParams для работы с URL-параметрами
  const params = new URLSearchParams(paramsString);
  // Возвращаем значение URL-параметра с именем game или null, если такого параметра нет
  return params.get('game') || null;
}

// Создаем функцию для отображения данных об игроках и игре на игровом интерфейсе
function displayGameInfo(data) {
  // Заполняем элементы с данными об игроках и игре из полученного объекта data
  player1Photo.src = data.player1.photo;
  player1Name.textContent = data.player1.name;
  player1Level.textContent = 'Уровень: ' + data.player1.level;
  player1Hp.textContent = 'Здоровье: ' + data.player1.hp;
  player1Coins.textContent = 'Монеты: ' + data.player1.coins;
  player1Damage.textContent = 'Урон: ' + data.player1.damage;

  player2Photo.src = data.player2.photo;
  player2Name.textContent = data.player2.name;
  player2Level.textContent = 'Уровень: ' + data.player2.level;
  player2Hp.textContent = 'Здоровье: ' + data.player2.hp;
  player2Coins.textContent = 'Монеты: ' + data.player2.coins;
  player2Damage.textContent = 'Урон: ' + data.player2.damage;

  gameTurn.textContent = 'Ход: ' + ((data.turn == playerId) ? 'Ваш' : 'Соперника');
  gameStatus.textContent = 'Статус: ' + data.status;
  gameWinner.textContent = (data.status == 'finished') ? ('Победитель: ' + data.winner.name) : '';

  // Очищаем элементы с предметами игроков от предыдущего содержимого
  player1Items.innerHTML = '';
  player2Items.innerHTML = '';

  // Для каждого предмета первого игрока
  for (let item of data.player1.items) {
    // Создаем элемент span для отображения предмета
    let span = document.createElement('span');
    // Задаем текстовое содержимое элемента в зависимости от ID предмета
    switch (item) {
      case 1:
        span.textContent = '+10 HP';
        break;
      case 2:
        span.textContent = span.textContent = '+1 Level';
        break;
      case 3:
        span.textContent = '+5 Damage';
        break;
      case 4:
        span.textContent = '+10 Damage';
        break;
      case 5:
        span.textContent = '+15 Damage';
        break;
      case 6:
        span.textContent = '+20 Damage';
        break;
    }
    // Добавляем элемент span в элемент с предметами первого игрока
    player1Items.appendChild(span);
  }

  // Для каждого предмета второго игрока
  for (let item of data.player2.items) {
    // Создаем элемент span для отображения предмета
    let span = document.createElement('span');
    // Задаем текстовое содержимое элемента в зависимости от ID предмета
    switch (item) {
      case 1:
        span.textContent = '+10 HP';
        break;
      case 2:
        span.textContent = '+1 Level';
        break;
      case 3:
        span.textContent = '+5 Damage';
        break;
      case 4:
        span.textContent = '+10 Damage';
        break;
      case 5:
        span.textContent = '+15 Damage';
        break;
      case 6:
        span.textContent = '+20 Damage';
        break;
    }
    // Добавляем элемент span в элемент с предметами второго игрока
    player2Items.appendChild(span);
  }
}

// Создаем функцию для отображения игрового поля на игровом интерфейсе
function displayGameBoard(data) {
  // Очищаем элемент с игровым полем от предыдущего содержимого
  gameBoard.innerHTML = '';
  // Сохраняем состояние игрового поля из полученного объекта data в переменную board
  board = data.board;
  // Для каждой ячейки игрового поля
  for (let i = 0; i < board.length; i++) {
    for (let j = 0; j < board[i].length; j++) {
      // Создаем элемент div для отображения ячейки
      let cell = document.createElement('div');
      // Задаем класс cell для элемента div
      cell.className = 'cell';
      // Задаем атрибут data-x с координатой x ячейки
      cell.setAttribute('data-x', i);
      // Задаем атрибут data-y с координатой y ячейки
      cell.setAttribute('data-y', j);
      // Задаем дополнительный класс cell-n для элемента div в зависимости от значения ячейки, где n - число от 1 до 6
      cell.classList.add('cell-' + board[i][j]);
      // Добавляем элемент div в элемент с игровым полем
      gameBoard.appendChild(cell);
    }
  }
}

// Создаем функцию для отображения друзей игрока и приглашения в игру на игровом интерфейсе
function displayGameFriends(data) {
  // Очищаем элемент с друзьями игрока от предыдущего содержимого
  gameFriendsCarousel.innerHTML = '';
  // Если у игрока есть друзья
  if (data.friends.length > 0) {
    // Для каждого друга игрока
    for (let friend of data.friends) {
      // Создаем элемент div для отображения друга
      let friendDiv = document.createElement('div');
      // Задаем класс friend для элемента div
      friendDiv.className = 'friend';
      // Создаем элемент img для отображения фото друга
      let friendPhoto = document.createElement('img');
      // Задаем атрибут src с URL фото друга
      friendPhoto.src = friend.photo;
      // Задаем атрибут alt с именем друга
      friendPhoto.alt = 'Фото ' + friend.name;
      // Добавляем элемент img в элемент div
      friendDiv.appendChild(friendPhoto);
      // Создаем элемент span для отображения имени друга