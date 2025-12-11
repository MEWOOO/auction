<?php
include("db.php");
session_start();
include("header.php");
?>

<main class="catalog">

  <!-- ======= ПОИСК ======= -->
  <div class="auction-search">
      <form method="GET" action="">
          <input type="text" name="q" placeholder="Поиск лота…"
                 value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
          <button type="submit" class="btn">Найти</button>
      </form>
  </div>

  <div class="catalog-layout">

    <!-- ======= ФИЛЬТРЫ ======= -->
    <aside class="filters">
      <h3>Фильтр аукционов</h3>
      <form method="GET" action="">
        <label>Категория:</label>
        <select name="category">
          <option value="">Все категории</option>
          <option value="Электроника">Электроника</option>
          <option value="Одежда">Одежда</option>
          <option value="Мебель">Мебель</option>
          <option value="Книги">Книги</option>
          <option value="Прочее">Прочее</option>
        </select>

        <label>Минимальная цена:</label>
        <input type="number" name="min_price" step="0.01">

        <label>Максимальная цена:</label>
        <input type="number" name="max_price" step="0.01">

        <button type="submit" class="btn small" style="margin-top:10px;">Применить</button>
      </form>
    </aside>

    <!-- ======= ПРАВАЯ ЧАСТЬ: СОРТИРОВКА + ТОВАРЫ ======= -->
    <div class="content-area">

      <!-- ======= КНОПКИ СОРТИРОВКИ ======= -->
      <?php $currentSort = $_GET['sort'] ?? ''; ?>

<div class="sort-buttons">
    <span class="sort-label">Сортировать:</span>

    <a href="?sort=price_asc"
       class="sort-btn <?php echo $currentSort=='price_asc' ? 'active-sort' : ''; ?>">
       Цена ↑
    </a>

    <a href="?sort=price_desc"
       class="sort-btn <?php echo $currentSort=='price_desc' ? 'active-sort' : ''; ?>">
       Цена ↓
    </a>

    <a href="?sort=date_new"
       class="sort-btn <?php echo $currentSort=='date_new' ? 'active-sort' : ''; ?>">
       Недавние
    </a>

    <a href="?sort=date_old"
       class="sort-btn <?php echo $currentSort=='date_old' ? 'active-sort' : ''; ?>">
       Старые
    </a>

    <a href="?sort=end_soon"
       class="sort-btn <?php echo $currentSort=='end_soon' ? 'active-sort' : ''; ?>">
       Скоро закончатся
    </a>
</div>


      <!-- ======= СПИСОК ТОВАРОВ ======= -->
      <section class="product-list">
      <?php

      // Базовые условия
      $where = "WHERE is_closed = 0 AND end_time > NOW()";

      // === ПОИСК ===
      if (!empty($_GET['q'])) {
          $q = $conn->real_escape_string($_GET['q']);
          $where .= " AND title LIKE '%$q%'";
      }

      // === ФИЛЬТРЫ ===
      if (!empty($_GET['category'])) {
          $category = $conn->real_escape_string($_GET['category']);
          $where .= " AND category = '$category'";
      }

      if (!empty($_GET['min_price'])) {
          $min = floatval($_GET['min_price']);
          $where .= " AND current_price >= $min";
      }

      if (!empty($_GET['max_price'])) {
          $max = floatval($_GET['max_price']);
          $where .= " AND current_price <= $max";
      }

      // === СОРТИРОВКА ===
      $sort = "ORDER BY end_time ASC"; // по умолчанию
      if (!empty($_GET['sort'])) {
          switch ($_GET['sort']) {
              case "price_asc":
                  $sort = "ORDER BY current_price ASC";
                  break;
              case "price_desc":
                  $sort = "ORDER BY current_price DESC";
                  break;
              case "date_new":
                  $sort = "ORDER BY id DESC";
                  break;
              case "date_old":
                  $sort = "ORDER BY id ASC";
                  break;
              case "end_soon":
                  $sort = "ORDER BY end_time ASC";
                  break;
          }
      }

      // Итоговый SQL
      $sql = "SELECT * FROM products $where $sort";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {

              $timeLeft = strtotime($row['end_time']) - time();

              if ($timeLeft > 0) {
                  $days = floor($timeLeft / 86400);
                  $hours = floor(($timeLeft % 86400) / 3600);
                  $minutes = floor(($timeLeft % 3600) / 60);
                  $timeText = "";
                  if ($days > 0) $timeText .= "$days д ";
                  if ($hours > 0) $timeText .= "$hours ч ";
                  $timeText .= "$minutes мин";
              } else {
                  $timeText = "Аукцион завершён";
              }

              echo "
              <div class='product-card'>
                <img src='uploads/{$row['image']}' alt='{$row['title']}'>
                <h3>{$row['title']}</h3>
                <p><strong>Текущая цена:</strong> {$row['current_price']} ₽</p>
                <p><strong>Осталось:</strong> $timeText</p>
                <a href='auction.php?id={$row['id']}' class='btn small'>Подробнее</a>
              </div>";
          }
      } else {
          echo "<p>Ничего не найдено.</p>";
      }
      ?>
      </section>
    </div>
  </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
