-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 29, 2026 at 02:51 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travel`
--

-- --------------------------------------------------------

--
-- Table structure for table `places`
--

CREATE TABLE `places` (
  `id` int NOT NULL,
  `travel_id` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `places`
--

INSERT INTO `places` (`id`, `travel_id`, `nom`, `latitude`, `longitude`) VALUES
(2, 4, 'Nara', 38.8927368, -77.0229201),
(3, 4, 'Rabat', 34.0218454, -6.8408929),
(4, 4, 'Tourcoing', 50.7235038, 3.1605714),
(5, 5, 'Paris', 48.8534951, 2.3483915),
(6, 5, 'Lille', 50.6365654, 3.0635282),
(7, 5, 'Montpellier', 43.6112422, 3.8767337),
(8, 5, 'Marseille', 43.2963986, 5.3777888),
(9, 3, 'tokyo', 35.6768601, 139.7638947),
(10, 3, 'kyoto', 35.0115754, 135.7681441),
(11, 3, 'nara', 38.8927368, -77.0229201),
(12, 3, 'osaka', 34.6937569, 135.5014539),
(13, 3, 'hiroshima', 34.3917241, 132.4517589),
(14, 3, 'nagasaki', 33.1154683, 129.7874339),
(15, 3, 'miyajima', 34.271448, 132.3088722),
(16, 3, 'fuji', 35.3628384, 138.7307677),
(17, 3, 'Koya', 34.215788, 135.5872944),
(18, 3, 'Takasaki', 36.3220984, 139.0032758),
(19, 3, 'Azumino', 36.3044083, 137.9054972),
(20, 6, 'Lille', 50.6365654, 3.0635282),
(21, 6, 'Dunkerque', 51.0347708, 2.3772525),
(22, 6, 'Paris', 48.8534951, 2.3483915),
(23, 6, 'Marseille', 43.2963986, 5.3777888),
(24, 7, 'Paris', 48.8534951, 2.3483915),
(25, 7, 'Lille', 50.6365654, 3.0635282),
(26, 7, 'Marseille', 43.2963986, 5.3777888),
(27, 7, 'Strasbourg', 48.584614, 7.7507127),
(28, 7, 'Nantes', 47.2186371, -1.5541362),
(29, 7, 'Tourcoing', 50.7235038, 3.1605714),
(30, 7, 'Nice', 43.7009358, 7.2683912),
(31, 8, 'Paris', 48.8588897, 2.320041),
(32, 8, 'Marseille', 43.2963986, 5.3777888),
(34, 7, 'Paris, Texas', 33.6617962, -95.555513);

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int NOT NULL,
  `travel_id` int NOT NULL,
  `distance_totale` double DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `travel_id`, `distance_totale`) VALUES
(1, 5, 1853.185),
(2, 5, 1853.185),
(3, 5, 1853.185),
(4, 3, 24275.132),
(5, 5, 1853.185),
(6, 5, 1853.185),
(7, 3, 24275.132),
(8, 5, 1762.29),
(9, 4, 14329.275),
(10, 3, 23717.673),
(11, 6, 1805.281),
(12, 6, 1805.281),
(13, 6, 1833.018),
(14, 5, 1837.325),
(15, 5, 1853.185),
(16, 5, 1837.325),
(17, 5, 1837.325),
(18, 5, 1837.325),
(19, 5, 2923.132),
(20, 5, 1853.185),
(21, 5, 1853.185),
(22, 5, 1837.325),
(23, 5, 1837.325),
(24, 3, 24443.299),
(25, 4, 14329.275),
(26, 7, 2610.778),
(27, 4, 14329.275),
(28, 3, 24443.299),
(29, 3, 25833.998),
(30, 7, 2610.778),
(31, 7, 2379.935),
(32, 7, 17388.652),
(33, 7, 17619.495),
(34, 7, 17388.652),
(35, 8, 1325.133),
(36, 5, 1837.325),
(37, 7, 17388.652);

-- --------------------------------------------------------

--
-- Table structure for table `travels`
--

CREATE TABLE `travels` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `titre` varchar(255) NOT NULL,
  `statut` enum('public','private') DEFAULT 'private',
  `ordered_route` text,
  `share_token` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `travels`
--

INSERT INTO `travels` (`id`, `user_id`, `titre`, `statut`, `ordered_route`, `share_token`) VALUES
(2, 3, 'japon', 'private', NULL, NULL),
(3, 5, 'japon', 'public', NULL, NULL),
(4, 5, 'Maroc', 'private', NULL, NULL),
(5, 5, 'France', 'private', NULL, NULL),
(6, 5, 'France2', 'private', NULL, NULL),
(7, 5, 'France3', 'public', NULL, NULL),
(8, 5, 'ROad trip', 'private', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `pseudo` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `pseudo`, `email`, `password`) VALUES
(1, 'test', 'test@test.fr', '$2y$12$0xRh34spUMcu5POGeEsil.8s1s1Oswvz4b2wH3vmEAILK099rzgWi'),
(3, 'noe', 'noe.labbe29@gmail.com', '$2y$12$T35HDB/VyjVpOHdZJ2GdE.k67HtHrN7MOGcN9QfLBfgH.oR8lXEvq'),
(5, 'issam', 'issam@gmail.com', '$2y$12$HewPd14Uvx3VLQCQ1EiSeeoDHS3yKbqd31cdk69OAzBRdzaUJWDiS');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `places`
--
ALTER TABLE `places`
  ADD PRIMARY KEY (`id`),
  ADD KEY `travel_id` (`travel_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `travel_id` (`travel_id`);

--
-- Indexes for table `travels`
--
ALTER TABLE `travels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `share_token` (`share_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `places`
--
ALTER TABLE `places`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `travels`
--
ALTER TABLE `travels`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `places`
--
ALTER TABLE `places`
  ADD CONSTRAINT `places_ibfk_1` FOREIGN KEY (`travel_id`) REFERENCES `travels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`travel_id`) REFERENCES `travels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `travels`
--
ALTER TABLE `travels`
  ADD CONSTRAINT `travels_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
