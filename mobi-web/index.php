<?php

if ($page->is_computer() || $page->is_spider()) {
  header("Location: ./about/");
} else {
  header("Location: ./home/");
}

?>
