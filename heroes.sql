-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20261014.d250d31a41
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 06, 2026 at 03:03 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fightcards`
--

-- --------------------------------------------------------

--
-- Table structure for table `heroes`
--
CREATE TABLE `heroes` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `health_point` int NOT NULL DEFAULT '100',
  `class_name` varchar(20) NOT NULL DEFAULT 'hero',
  `mana_point` int NOT NULL DEFAULT '30',
  `image_path` varchar(255) DEFAULT NULL,
  `dodge_chance_basis_points` bigint UNSIGNED NOT NULL DEFAULT '0',
  `monsters_survived` int UNSIGNED NOT NULL DEFAULT '0',
  `rogue_card_upgrades` varchar(512) NOT NULL DEFAULT '[]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `heroes`
--

INSERT INTO `heroes` (`id`, `name`, `health_point`, `class_name`, `mana_point`, `image_path`, `dodge_chance_basis_points`, `monsters_survived`, `rogue_card_upgrades`) VALUES
(4, 'Dummy', -8, 'hero', 30, NULL, 0, 0, '[]'),
(6, 'Dummy', -4, 'hero', 30, NULL, 0, 0, '[]'),
(7, 'Dummy', -9, 'hero', 30, NULL, 0, 0, '[]'),
(8, 'Dummy', -24, 'hero', 30, NULL, 0, 0, '[]'),
(9, 'Dummy', 0, 'hero', 30, NULL, 0, 0, '[]'),
(10, 'Dummy', -6, 'hero', 30, NULL, 0, 0, '[]'),
(11, 'Dummy', -4, 'hero', 30, NULL, 0, 0, '[]'),
(12, 'Dummy', -32, 'mage', 30, NULL, 0, 0, '[]'),
(13, 'Dummy', -1, 'guerrier', 30, NULL, 0, 0, '[]'),
(14, 'Dummy', -9, 'mage', 30, NULL, 0, 0, '[]'),
(15, 'Dummy', -49, 'mage', 30, NULL, 0, 0, '[]'),
(16, 'Dummy', -6, 'mage', 30, NULL, 0, 0, '[]'),
(17, 'Dummy', -19, 'guerrier', 30, NULL, 0, 0, '[]'),
(18, 'Dummy', -26, 'archer', 30, NULL, 0, 0, '[]'),
(19, 'Dummy', -44, 'guerrier', 30, NULL, 0, 0, '[]'),
(20, 'Dummy', -22, 'guerrier', 30, NULL, 0, 0, '[]'),
(21, 'Dummy', -2, 'guerrier', 30, NULL, 0, 0, '[]'),
(22, 'Dummy', -76, 'mage', 30, NULL, 0, 0, '[]'),
(23, 'Dummy', 0, 'mage', 23, NULL, 0, 0, '[]'),
(24, 'Dummy', 0, 'vampire', 26, NULL, 0, 0, '[]'),
(25, 'Dummy', 0, 'mage', 26, NULL, 0, 0, '[]'),
(26, 'Dummy', 0, 'archer', 15, NULL, 0, 0, '[]'),
(27, 'Dummy', 0, 'mage', 12, NULL, 0, 0, '[]'),
(28, 'Edmond', 0, 'vampire', 30, 'uploads/heroes/f7ef34154f52652ae20dea847c0de0e4.webp', 0, 0, '[]'),
(29, 'Dummy', 0, 'guerrier', 30, 'uploads/heroes/50ed28597571ba1e8283183b639f818e.webp', 0, 0, '[]'),
(30, 'Dummy', 0, 'guerrier', 15, 'uploads/heroes/19b4041b9b823b6dffd8b9af67b2a9cf.webp', 0, 1, '[]'),
(32, 'Edmond', 0, 'vampire', 9, 'uploads/heroes/b4e1f4549ba0e20602d83f05fef7bbfa.webp', 0, 3, '[]'),
(34, 'Edmond', 0, 'vampire', 30, 'uploads/heroes/c29311ef0ea9fd43de0aadf3a918d810.webp', 0, 0, '[]'),
(35, 'Bark', 0, 'mage', 30, 'uploads/heroes/d47424a79691606f4d3d779c810f3592.png', 0, 0, '[]'),
(36, 'Bark', 0, 'mage', 16, 'uploads/heroes/9d07dde7670395c3ec78bb9b38150a32.png', 0, 3, '[]'),
(37, 'Bark', 0, 'archer', 30, 'uploads/heroes/5e65657bf17782ac15340fc4ff6c0ae9.png', 2000, 0, '[]'),
(38, 'Miku', 0, 'vampire', 16, 'uploads/heroes/d729e012eb7fed9138fc1de7a05facf2.png', 0, 7, '[]'),
(39, 'Dummy', 0, 'archer', 8, 'uploads/heroes/4d911ab0063fcfe74270ec7530af0965.webp', 3125, 0, '[]'),
(40, 'Dummy', 0, 'archer', 22, 'uploads/heroes/307c9c069b81f8d6383d116e26f2b0e9.webp', 2500, 0, '[]'),
(41, 'Dummy', 0, 'archer', 5, 'uploads/heroes/fb4855900d833bc3bfb24c55de00050d.webp', 3125, 0, '[]'),
(42, 'Edmond', 0, 'vampire', 30, 'uploads/heroes/c7e724cc9c8426271047a0bca74b1e8b.webp', 0, 2, '[]'),
(43, 'Gambler', 0, 'gambler', 9, 'uploads/heroes/15dbd706d1f5abb4e67685fa1b664edc.jpg', 0, 0, '[]'),
(44, 'Dummy', 0, 'gambler', 12, 'uploads/heroes/6b30ab55c3f0f96e05a7d555080ee5af.jpg', 0, 0, '[]'),
(45, 'Dummy', 0, 'gambler', 9, 'uploads/heroes/114d67c6ec806900899c6c3b0b158d29.jpg', 0, 0, '[]'),
(46, 'Dummy', 0, 'gambler', 15, 'uploads/heroes/688771927c372828c4f8f8b634271430.jpg', 0, 1, '[]'),
(47, 'Dummy', 0, 'gambler', 9, 'uploads/heroes/dc677392070c63b0c9d19fbd6ca4ce47.jpg', 0, 4, '[]'),
(48, 'Gamber', 0, 'gambler', 6, 'uploads/heroes/a2ac714f2be01fbef084367d49d683f4.jpg', 0, 0, '[]'),
(49, 'Dummy', 67, 'guerrier', 30, 'uploads/heroes/6c70617672d7d3987a1cd756c538aa2b.webp', 0, 1, '[]'),
(50, 'Edmond', 0, 'vampire', 30, 'uploads/heroes/d493f55d0d967fe01347c4ab38927dfd.webp', 0, 0, '[]'),
(51, 'Elvine', 0, 'archer', 18, 'uploads/heroes/8efa8ef525b177c31e54f5614df95d58.webp', 71070, 5, '[\"VampirismUpgrade\",\"ManaRegenerationUpgrade\",\"MaxDodgeUpgrade\",\"MaxDefenseUpgrade\",\"MaxHealthUpgrade\"]'),
(52, 'Jevil', 76, 'mage', 30, 'uploads/heroes/56996b9ec12c24a31d9f46ea5b8d8e3c.webp', 0, 6, '[]'),
(53, 'Jarona', 0, 'vampire', 19, 'uploads/heroes/094fb40549e182dbc3fe13589ac471f3.webp', 0, 0, '[]'),
(54, 'Dummy', 0, 'guerrier', 30, 'uploads/heroes/db9df6db6325f07ec10687d45e394b04.webp', 0, 0, '[]'),
(55, 'Jarona', 0, 'vampire', 30, 'uploads/heroes/de0812d22aed186371f3a57072f83ab4.webp', 0, 0, '[]'),
(56, 'Jarona', 0, 'vampire', 30, 'uploads/heroes/68a53bef0b60652bbbb60f676d0abeab.webp', 0, 1, '[]'),
(57, 'Jarona', 0, 'vampire', 14, 'uploads/heroes/5f0d48ecfee25193c064790bf89b75de.webp', 0, 2, '[]'),
(58, 'Jarona', 0, 'vampire', 8, 'uploads/heroes/d8a57929926af9587c6e668d7d1776f3.webp', 0, 5, '[]'),
(59, 'Jack', 0, 'werewolf', 3, 'uploads/heroes/8f599784a40e7214e818a3ac8d7fad3b.png', 0, 5, '[]'),
(60, 'Jack', 0, 'werewolf', 16, 'uploads/heroes/55b436bce5062ec99f0704fbef11e4f7.png', 0, 2, '[]'),
(61, 'Jarona', 0, 'vampire', 13, 'uploads/heroes/a0d6e3c1f7069d9895713d208871abed.webp', 0, 3, '[]'),
(62, 'Jarona', 999, 'vampire', 999, 'uploads/heroes/1d0186ffe66139e86efe7309877dd9fd.webp', 0, 88, '[\"RegenerationUpgrade\",\"MaxDodgeUpgrade\"]'),
(63, 'Edmond', 0, 'vampire', 13, 'uploads/heroes/329b741164b84d276ef9c27936d1024b.webp', 0, 5, '[]'),
(64, 'Jack', 0, 'werewolf', 10, 'uploads/heroes/6fa4436f03ef4240f7d97e4d0c42266e.png', 0, 5, '[\"MaxDMGUpgrade\",\"RegenerationUpgrade\",\"MaxDodgeUpgrade\",\"ManaRegenerationUpgrade\",\"MaxHealthUpgrade\"]'),
(65, 'Edmond', 0, 'vampire', 30, 'uploads/heroes/9d732ce10755379e39f6481403aafb82.webp', 0, 15, '[\"MaxDMGUpgrade\",\"MaxDefenseUpgrade\",\"VampirismUpgrade\",\"MaxHealthUpgrade\"]'),
(67, 'Edmond', 100, 'vampire', 30, 'uploads/heroes/0c4d8c04e3eae42c8d34c7a4a4296b26.webp', 0, 0, '[]'),
(68, 'Elvine', 0, 'archer', 20, 'uploads/heroes/417c415207159cc830c457ff832a0ff5.webp', 9399, 5, '[\"MaxAllOutUpgrade\",\"RegenerationUpgrade\",\"InvulnerabiltyPoison\",\"InvulnerabiltyFire\",\"MaxDodgeUpgrade\"]'),
(69, 'Marksman', 100, 'gambler', 30, 'uploads/heroes/cc35de5ed9f2f2e4fefd1f7fa343377b.jpg', 0, 0, '[]'),
(70, 'Jack', 100, 'werewolf', 30, 'uploads/heroes/cb5585eabf81722f12231796465c86d8.png', 0, 0, '[]'),
(71, 'Elvine', 100, 'archer', 30, 'uploads/heroes/c6a8ca5db25c8f8b932bdec8bbca7e07.webp', 2000, 0, '[]'),
(72, 'noob', 0, 'gambler', 29, 'uploads/heroes/3c8057829a413eb38d81a0a5e4aed391.jpg', 0, 1, '[\"MaxManaUpgrade\"]');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `heroes`
--
ALTER TABLE `heroes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `heroes`
--
ALTER TABLE `heroes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
