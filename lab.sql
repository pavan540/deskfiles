-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 09:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lab`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `name`, `type`) VALUES
('24IT281', 'Object Oriented Programming - C++ Lab', 'PRACTICAL'),
('24IT282', 'Adv. Data Structures Lab', 'PRACTICAL'),
('24IT283', 'Web Programming', 'PRACTICAL');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(150) NOT NULL,
  `school_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`, `school_id`) VALUES
(1, 'Civil Engineering', 1),
(2, 'Computer Science and Engineering', 1),
(3, 'Electronics and Communication Engineering', 1),
(4, 'Electrical and Electronics Engineering', 1),
(5, 'Electronics and Instrumentation Engineering', 1),
(6, 'Information Technology', 1),
(7, 'Mechanical Engineering', 1),
(8, 'Chemistry', 1),
(9, 'English', 1),
(10, 'Mathematics', 1),
(11, 'Physics', 1),
(12, 'Computer Applications', 2),
(13, 'Business Administration', 3),
(14, 'Arts & Commerce', 4),
(15, 'Master of Law', 5),
(16, 'CSE (AI&DS)', 1),
(17, 'CSE (AI&ML)', 1);

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` varchar(10) NOT NULL,
  `password` varchar(25) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `bank_branch_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(30) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `password`, `name`, `department`, `phone`, `designation`, `bank_branch_name`, `account_number`, `ifsc_code`, `email`) VALUES
('12345', '12345', 'ramesh mande', 'IT', '123456789', 'Sr. Assistant Professor', 'VRSEC', '30432010099999', '12345678911', 'ramesh.welcome@gmail.com'),
('25064', '25064', 'chitturi satya pavan kumar', 'it', '8985670186', 'Sr. Assistant Professor', 'VRSEC', '11425099152', 'cnrb0012535', 'pavanchitturi@vrsiddhartha.ac.in'),
('321456', '321456', 'balaji', 'IT', '555555555', 'Skilled Assistant', 'VRSEC', '12345678912', '1121454121', 'BALU@GMAIL.COM'),
('654123', '654123', 'sri lakshmi', 'it', '777777777', 'deo', 'vrsec', '555555555', '555555', 'sri@gmail.com'),
('987456', '987456', 'KOTI', 'IT', '6666666666', 'ATTENDER', 'VRSEC', '5555656565', '55454544', 'koti@gmail.com'),
('99999', '99999', 'admin', 'admin', '111111111', 'Sr. Assistant Professor', 'VRSEC', '11425099152', 'cnrb0012535', 'pavan540.mic@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `fvc`
--

CREATE TABLE `fvc` (
  `faculty_id` varchar(10) NOT NULL,
  `ext_faculty_id` varchar(10) DEFAULT NULL,
  `course_id` varchar(10) NOT NULL,
  `section` varchar(10) NOT NULL,
  `dept` int(11) NOT NULL,
  `sem` varchar(50) DEFAULT NULL,
  `AY` varchar(20) NOT NULL,
  `mon_year` varchar(20) DEFAULT NULL,
  `programme_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fvc`
--

INSERT INTO `fvc` (`faculty_id`, `ext_faculty_id`, `course_id`, `section`, `dept`, `sem`, `AY`, `mon_year`, `programme_id`) VALUES
('12345', '25064', '24IT281', '1', 6, '3', '2025-26', 'November, 2025', 3),
('12345', '25064', '24IT281', '2', 6, '3', '2025-26', 'November, 2025', 3),
('25064', '12345', '24IT281', '3', 6, '3', '2025-26', 'November, 2025', 3),
('25064', '12345', '24IT282', '1', 6, '3', '2025-26', 'November, 2025', 3),
('12345', '654123', '24IT282', '2', 6, '3', '2025-26', 'November, 2025', 3),
('25064', '12345', '24IT282', '3', 6, '3', '2025-26', 'November, 2025', 3);

-- --------------------------------------------------------

--
-- Table structure for table `fvc_schedule`
--

CREATE TABLE `fvc_schedule` (
  `schedule_id` int(11) NOT NULL,
  `course_id` varchar(20) NOT NULL,
  `dept` int(11) NOT NULL,
  `AY` varchar(20) NOT NULL,
  `section` varchar(10) NOT NULL,
  `start_roll_no` varchar(20) NOT NULL,
  `end_roll_no` varchar(20) NOT NULL,
  `exam_date` date NOT NULL,
  `session` enum('FN','AN') NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fvc_schedule`
--

INSERT INTO `fvc_schedule` (`schedule_id`, `course_id`, `dept`, `AY`, `section`, `start_roll_no`, `end_roll_no`, `exam_date`, `session`, `remarks`, `created_at`) VALUES
(10, '24IT281', 6, '2025-26', '3', '24EU08133', '24EU08165', '2025-10-23', 'FN', '', '2025-10-23 14:21:14'),
(11, '24IT281', 6, '2025-26', '3', '24EU08166', '24EU08198', '2025-10-23', 'AN', '', '2025-10-23 14:21:14'),
(12, '24IT281', 6, '2025-26', '2', '24EU08067', '24EU08099', '2025-11-12', 'FN', '', '2025-10-24 10:48:54'),
(13, '24IT281', 6, '2025-26', '2', '24EU08100', '24EU08132', '2025-11-20', 'AN', '', '2025-10-24 10:48:54'),
(14, '24IT281', 6, '2025-26', '1', '24EU08002', '24EU08035', '2025-10-22', 'FN', '', '2025-10-24 10:50:33'),
(15, '24IT281', 6, '2025-26', '1', '24EU08036', '24EU08066', '2025-11-04', 'FN', '', '2025-10-24 10:50:33'),
(16, '24IT281', 6, '2025-26', '1', '24EU08002', '24EU08035', '2025-10-25', 'FN', '', '2025-10-25 03:17:53'),
(17, '24IT281', 6, '2025-26', '1', '24EU08036', '24EU08066', '2025-10-25', 'AN', '', '2025-10-25 03:17:53'),
(18, '24IT282', 6, '2025-26', '1', '24EU08002', '24EU08035', '2025-10-25', 'FN', '', '2025-10-25 08:02:01'),
(19, '24IT282', 6, '2025-26', '1', '24EU08036', '24EU08066', '2025-10-25', 'AN', '', '2025-10-25 08:02:01'),
(20, '24IT282', 6, '2025-26', '2', '24EU08067', '24EU08132', '2025-10-31', 'FN', '', '2025-10-25 08:02:51'),
(21, '24IT282', 6, '2025-26', '3', '24EU08133', '24EU08198', '2025-11-26', 'FN', '', '2025-10-25 08:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `roll_no` varchar(10) NOT NULL,
  `course_id` varchar(10) NOT NULL,
  `section` varchar(10) NOT NULL,
  `dept` int(100) NOT NULL,
  `AY` varchar(20) NOT NULL,
  `procedure_marks` int(11) DEFAULT 0,
  `viva_marks` int(11) DEFAULT 0,
  `result_marks` int(11) DEFAULT 0,
  `experiment_marks` int(11) DEFAULT 0,
  `total_marks` int(11) DEFAULT 0,
  `total_in_words` varchar(255) DEFAULT 'Zero',
  `is_absent` tinyint(1) DEFAULT 0,
  `is_finalized` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programmes`
--

CREATE TABLE `programmes` (
  `programme_id` int(11) NOT NULL,
  `programme_name` varchar(100) NOT NULL,
  `school_id` int(11) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `level` enum('UG','PG','Ph.D') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programmes`
--

INSERT INTO `programmes` (`programme_id`, `programme_name`, `school_id`, `dept_id`, `level`) VALUES
(1, 'B.Tech', 1, 1, 'UG'),
(2, 'B.Tech', 1, 2, 'UG'),
(3, 'B.Tech', 1, 6, 'UG'),
(4, 'M.Tech', 1, 1, 'PG'),
(5, 'M.Tech', 1, 2, 'PG'),
(6, 'Ph.D', 1, 1, 'Ph.D'),
(7, 'MCA', 2, 4, 'PG'),
(8, 'BBA', 3, 5, 'UG'),
(9, 'MBA', 3, 5, 'PG'),
(10, 'B.Com', 4, 6, 'UG'),
(11, 'M.Com', 4, 6, 'PG'),
(12, 'BLL', 5, 7, 'UG'),
(13, 'MLL', 5, 7, 'PG');

-- --------------------------------------------------------

--
-- Table structure for table `remuneration`
--

CREATE TABLE `remuneration` (
  `id` int(11) NOT NULL,
  `course_id` varchar(10) NOT NULL,
  `section` varchar(10) NOT NULL,
  `dept` int(11) NOT NULL,
  `AY` varchar(20) NOT NULL,
  `faculty_id` varchar(10) NOT NULL,
  `examiner1_id` varchar(10) NOT NULL,
  `examiner2_id` varchar(10) NOT NULL,
  `tech_name` varchar(100) NOT NULL,
  `deo_name` varchar(100) NOT NULL,
  `peon_name` varchar(100) NOT NULL,
  `total_candidates` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `remuneration`
--

INSERT INTO `remuneration` (`id`, `course_id`, `section`, `dept`, `AY`, `faculty_id`, `examiner1_id`, `examiner2_id`, `tech_name`, `deo_name`, `peon_name`, `total_candidates`, `created_at`, `updated_at`) VALUES
(1, '24IT281', '3', 6, '2025-26', '25064', '25064', '12345', 'balaji', 'sri lakshmi', 'balaji', 64, '2025-10-25 02:39:54', '2025-10-28 12:17:42'),
(2, '24IT282', '1', 6, '2025-26', '25064', '25064', '12345', 'balaji', 'balaji', 'balaji', 62, '2025-10-25 08:24:00', '2025-10-28 12:17:48');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `school_id` int(11) NOT NULL,
  `school_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`school_id`, `school_name`) VALUES
(4, 'School of Arts & Commerce'),
(5, 'School of Law'),
(3, 'School of Management'),
(2, 'School of Sciences'),
(1, 'V R Siddhartha School of Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `roll_no` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `branch` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`roll_no`, `name`, `section`, `branch`) VALUES
('24EU08002', 'AFNAN ABDUL RASHEED', '1', '6'),
('24EU08003', 'AKULA SOWJANYA LAKSHMI', '1', '6'),
('24EU08004', 'ALAMURI HIMA VENKAT', '1', '6'),
('24EU08005', 'ANNE VARSHINI', '1', '6'),
('24EU08006', 'ATLURI SOHAN', '1', '6'),
('24EU08007', 'BATTULA LASYA CHANDRIKA', '1', '6'),
('24EU08008', 'BIRABOINA SARANYA', '1', '6'),
('24EU08009', 'BOJJA JDEEP PHANI', '1', '6'),
('24EU08010', 'BOYINA LAKSHMISHIVANI', '1', '6'),
('24EU08011', 'CHENNU DHAKSHAINI', '1', '6'),
('24EU08012', 'CHERUKURI SAI DIVYESH', '1', '6'),
('24EU08013', 'CHITTIBOMMA SHYAM KUMAR', '1', '6'),
('24EU08014', 'DAVULURU NAGA LAKSHMI', '1', '6'),
('24EU08015', 'GAMPALA VIMA SANJANA', '1', '6'),
('24EU08016', 'GINNI BHASKAR RAO', '1', '6'),
('24EU08017', 'GORIPARTHI CHARAN RAMA RAJU', '1', '6'),
('24EU08018', 'HIRANMAYE TANUKU', '1', '6'),
('24EU08019', 'ITTA SAI CHARITH', '1', '6'),
('24EU08020', 'KADAMBALA SRI HARSHA', '1', '6'),
('24EU08021', 'KALASANI MODHINI', '1', '6'),
('24EU08022', 'KATTA VENKATA SREEKAR', '1', '6'),
('24EU08023', 'KESIRAJU VENKATA HEMANTH', '1', '6'),
('24EU08024', 'KOMMANABOINA HEMANTH', '1', '6'),
('24EU08025', 'KOMMARAJU DWARAKI KRISHNA', '1', '6'),
('24EU08026', 'KOMMINENI GREESHMITHA', '1', '6'),
('24EU08027', 'KONA RISHITHA', '1', '6'),
('24EU08028', 'KONDAPALLI SRI SAI PRANAY', '1', '6'),
('24EU08029', 'KOSURU SIVA SRI SPURTHI', '1', '6'),
('24EU08030', 'KOTA RISHIK', '1', '6'),
('24EU08031', 'KOTHAPALLI DEEKSHITHA', '1', '6'),
('24EU08032', 'KUDUMULA MADHURIMA', '1', '6'),
('24EU08034', 'KUNAPULI SREE HARSHITHA', '1', '6'),
('24EU08035', 'KUNDETI SRI GANESH', '1', '6'),
('24EU08036', 'LOYA RITHIKRAM', '1', '6'),
('24EU08037', 'MAJETY SATHWIK', '1', '6'),
('24EU08038', 'MALLA KIRANMAYEE', '1', '6'),
('24EU08039', 'MANNEM RUPA DEVI', '1', '6'),
('24EU08040', 'MARAM GAYATHRI', '1', '6'),
('24EU08041', 'MATAM LAKSHMI BHANU MURTHY', '1', '6'),
('24EU08042', 'MOHAMMED AMAAN', '1', '6'),
('24EU08043', 'MOHAMMED REHAN KHAN', '1', '6'),
('24EU08044', 'MOHAMMED SHOAIB', '1', '6'),
('24EU08045', 'MOOLE POOJYA VENKATA VARDHAN REDDY', '1', '6'),
('24EU08046', 'MUNAGALA VEDA SRI NAGA SAI LAKSHMI', '1', '6'),
('24EU08047', 'NALAMOTHU NIKHIL', '1', '6'),
('24EU08048', 'PATHAN MOHSINA KHAN', '1', '6'),
('24EU08049', 'PILLI MAHA LAKSHMI', '1', '6'),
('24EU08050', 'RACHAMALLA ANIRUDH REDDY', '1', '6'),
('24EU08051', 'RELANGI KAVYA SREE', '1', '6'),
('24EU08052', 'RISHITA RATH', '1', '6'),
('24EU08053', 'SAMALA SAI PAVAN KUMAR', '1', '6'),
('24EU08054', 'SANIKOMMU HITESRI', '1', '6'),
('24EU08055', 'SONTI HANSIKA', '1', '6'),
('24EU08056', 'TALLAM JAYANTH DATTA', '1', '6'),
('24EU08057', 'THOKALA LAASYA', '1', '6'),
('24EU08058', 'TUNUGUNTLA TULASI LAKSHMI NAGA SAI LALITHA', '1', '6'),
('24EU08059', 'UDUTHA VENKATA ANJALI BHAVYA', '1', '6'),
('24EU08060', 'VAGICHARLA NAVYA SRI', '1', '6'),
('24EU08061', 'VALA SAI KIRTANA', '1', '6'),
('24EU08062', 'VANTAKULA SATWIK', '1', '6'),
('24EU08063', 'VEMULA KAMAL SAI', '1', '6'),
('24EU08064', 'YAMPATI SHALINI', '1', '6'),
('24EU08065', 'YANDURU SWATHI', '1', '6'),
('24EU08066', 'YARLAGADDA VAISHNAVI', '1', '6'),
('24EU08067', 'ALLADA GNANAMAI', '2', '6'),
('24EU08068', 'ATLURI LAASYA', '2', '6'),
('24EU08069', 'BANDIREDDY THANMAI', '2', '6'),
('24EU08070', 'BATHULA SAI VARSHITHA YADAV', '2', '6'),
('24EU08071', 'BODAPATI TRIVENI', '2', '6'),
('24EU08072', 'BOPPUDI LAKSHMI PRABHA', '2', '6'),
('24EU08073', 'CHALAPATI VENKATESWARA ADITHYA', '2', '6'),
('24EU08074', 'CHATRATHI VEERA MANIKANTA SAI CHARAN', '2', '6'),
('24EU08075', 'CHINKA VEERARAJU YADAV', '2', '6'),
('24EU08076', 'CHINNI HEMA SAI MANI DEEPIKA', '2', '6'),
('24EU08077', 'DESAMSETTY MANI PAVAN', '2', '6'),
('24EU08078', 'DINTAKURTHI VINANYA', '2', '6'),
('24EU08079', 'DOGIPARTHI SAI NAGA PIYUSH', '2', '6'),
('24EU08080', 'DONTHIREDDY DHARANI', '2', '6'),
('24EU08081', 'DUPAKUNTLA UMA SAI LEELA', '2', '6'),
('24EU08082', 'G N C S S GAYATHRI', '2', '6'),
('24EU08083', 'GANJI SHANMUK SAI SRINIVAS', '2', '6'),
('24EU08084', 'GNANESWAR SIRIPURAPU', '2', '6'),
('24EU08085', 'GONDI MYTHRI ROY', '2', '6'),
('24EU08086', 'GOVVALA HAMSINI', '2', '6'),
('24EU08087', 'GUNDAPANENI RUSHASWI', '2', '6'),
('24EU08088', 'GUTHULA GAYATRI', '2', '6'),
('24EU08089', 'IGUTURI SAI VARSHINI', '2', '6'),
('24EU08090', 'INAMPUDI CHANDANA', '2', '6'),
('24EU08091', 'KADAM MOHINDER SRI PHANI SAI', '2', '6'),
('24EU08092', 'KAILE JENNITH PRANAY', '2', '6'),
('24EU08093', 'KARAKAVALASA SAHITHI', '2', '6'),
('24EU08094', 'KOGANTI MEDHAVINI SREE', '2', '6'),
('24EU08095', 'KONERU HEMASWI', '2', '6'),
('24EU08096', 'KONIKI SANDHYA RANI', '2', '6'),
('24EU08097', 'KORRAPOLU HARSHINI', '2', '6'),
('24EU08098', 'KOSARAJU CHANDRESH', '2', '6'),
('24EU08099', 'LELLA MOSES EBENEZER', '2', '6'),
('24EU08100', 'MADDALI SAI DURGA NAVYA SRI', '2', '6'),
('24EU08101', 'MAGANTI NIKHIL SRINIVAS', '2', '6'),
('24EU08102', 'MAHANTHI JNANA ANANTH ESWAR', '2', '6'),
('24EU08103', 'MANUPATI SANTOSH', '2', '6'),
('24EU08104', 'MANYALA TINKU JANARDHANA ARJUN', '2', '6'),
('24EU08105', 'MATURI SRI DURGA PALLAVI', '2', '6'),
('24EU08106', 'MOHAMMED RAYYAN AHMED', '2', '6'),
('24EU08107', 'NAGARAJU AKSHAYA SREE', '2', '6'),
('24EU08108', 'NARRA HIMA KRISHNA KOWSHIK', '2', '6'),
('24EU08109', 'NARUGUNDLA MANOJ', '2', '6'),
('24EU08110', 'PATHAKAMUDI BINDU SRI', '2', '6'),
('24EU08111', 'PATHI VENKATA VINAY', '2', '6'),
('24EU08112', 'PERAKAM BINDU SREE', '2', '6'),
('24EU08113', 'REDROWTHU SHANMUKA SAI ESWAR', '2', '6'),
('24EU08114', 'REGALLA VENKATA SAI RAGHAVA', '2', '6'),
('24EU08115', 'SAMARLA RAKESH', '2', '6'),
('24EU08116', 'SHAIK IMRAN', '2', '6'),
('24EU08117', 'SHAIK REHAN', '2', '6'),
('24EU08118', 'SUFIYAN KHAN', '2', '6'),
('24EU08119', 'SUREDDI CHARISHMA', '2', '6'),
('24EU08120', 'SYED GOUSE', '2', '6'),
('24EU08121', 'THOTA TANUJA KRISHNA', '2', '6'),
('24EU08122', 'THOTAKURA NAGA SAI NIKHIL', '2', '6'),
('24EU08123', 'TUNGALA JITH JASWANTH', '2', '6'),
('24EU08124', 'UDARAPU DEEPAK', '2', '6'),
('24EU08125', 'UPPALAPATI JNANA SRI HITHA', '2', '6'),
('24EU08126', 'VEDANTAM KANAKA SRUTHI', '2', '6'),
('24EU08127', 'VEERAMACHANENI TANMAYI', '2', '6'),
('24EU08128', 'VELISETTY SARAN GOWRISH', '2', '6'),
('24EU08129', 'VEMIREDDY AAKASH', '2', '6'),
('24EU08130', 'VEPURI BHASWANTH', '2', '6'),
('24EU08131', 'YADLAPATI DHANURVEDI', '2', '6'),
('24EU08132', 'YARLAGADDA MANIKANTA', '2', '6'),
('24EU08133', 'AKKAPEDDI UTPALA VALLI', '3', '6'),
('24EU08134', 'AKURATHI SIVA NADH', '3', '6'),
('24EU08135', 'ANGULURI LIKHITHA', '3', '6'),
('24EU08136', 'ANUMUKONDA SIRI HASINI', '3', '6'),
('24EU08137', 'ATLURI KARTHIKEYAN CHOWDARY', '3', '6'),
('24EU08138', 'AVIRINENI RAVI KIRAN', '3', '6'),
('24EU08139', 'AVUTHU JASHVANTH REDDY', '3', '6'),
('24EU08140', 'BADI VEERA VENKATA AKSHAYA', '3', '6'),
('24EU08141', 'BAIG ASHRAF', '3', '6'),
('24EU08142', 'BITRA SRI VYSHNAVI', '3', '6'),
('24EU08143', 'CHALAMALA MADHUKAR', '3', '6'),
('24EU08144', 'CHARAN TEJA KOLLURI', '3', '6'),
('24EU08145', 'CHINNATHAMBI NARAYANAN POOJA CHANDRAN', '3', '6'),
('24EU08146', 'CHINTA DEESHMA DURGA', '3', '6'),
('24EU08147', 'CHINTALAPUDI S S V VENKATA VAMSI KRISHNA', '3', '6'),
('24EU08148', 'DASYAM SEVITHA', '3', '6'),
('24EU08149', 'DOPPALAPUDI HANISHA KRISHNA', '3', '6'),
('24EU08150', 'DUGGIRALA SRI DATTA KARTHIKEYA', '3', '6'),
('24EU08151', 'EEDIPILLI LOKESH', '3', '6'),
('24EU08152', 'GUJJARLAPUDI ADITYA VARDHAN', '3', '6'),
('24EU08153', 'GUJJULA RADHA SHERLYN', '3', '6'),
('24EU08154', 'GUJJURU NAGA SANTHOSH NIKHIL', '3', '6'),
('24EU08155', 'GUJJURU NANDA KISHORE NIKHITH', '3', '6'),
('24EU08156', 'GURRAM RUTVIJ KUMAR', '3', '6'),
('24EU08157', 'HABIBULLAH KHAN', '3', '6'),
('24EU08158', 'IRRINKI TEJA', '3', '6'),
('24EU08159', 'JALLU POOJITHA', '3', '6'),
('24EU08160', 'JANYAVULA RADHIKA', '3', '6'),
('24EU08161', 'KAJA VENKATA SAI KARTHIKEYA', '3', '6'),
('24EU08162', 'KOSARAJU SUNEHARI', '3', '6'),
('24EU08163', 'KOTHURU VENKATA SAI KRISHNA', '3', '6'),
('24EU08164', 'KUNDERU KUNDAN', '3', '6'),
('24EU08165', 'MADHU SRI CHINNAPOTHULA', '3', '6'),
('24EU08166', 'MOHAMMAD HABIBA', '3', '6'),
('24EU08167', 'MOHAMMAD MUNWAR BAIG', '3', '6'),
('24EU08168', 'MOHAMMAD ROUF AHMED', '3', '6'),
('24EU08169', 'MOHAMMAD TANVEER AHMED', '3', '6'),
('24EU08170', 'MUSUNURU PUJITH', '3', '6'),
('24EU08171', 'NAGANABOYINA MANI RATHNAM', '3', '6'),
('24EU08172', 'NAGELLA SUMANTH', '3', '6'),
('24EU08173', 'NAKKA SAILENDRA PRASAD', '3', '6'),
('24EU08174', 'NARAGANI PAVANI', '3', '6'),
('24EU08175', 'NARRA PAVAN SAI TEJ', '3', '6'),
('24EU08176', 'NERELLA NAGA SAI SREE LASYA', '3', '6'),
('24EU08177', 'NIRMAL M A', '3', '6'),
('24EU08178', 'PADIGAPATI SAI VAMSI REDDY', '3', '6'),
('24EU08179', 'PALEPU LAKSHMI HARSHITHA', '3', '6'),
('24EU08180', 'PAMARTHI YASASWI', '3', '6'),
('24EU08181', 'PANE SIVA JYOTHI PRAKASH', '3', '6'),
('24EU08182', 'PATAKULA ROHITH SAI KRISHNA', '3', '6'),
('24EU08183', 'PATIBANDLA LAASYA SRI', '3', '6'),
('24EU08184', 'PINISETTI IPSITHA', '3', '6'),
('24EU08185', 'PONNAGANTI PARNA SRI CHOUDARY', '3', '6'),
('24EU08186', 'SANIKOMMU GANESH CHARAN REDDY', '3', '6'),
('24EU08187', 'SHAIK LIHANA SHARMIN', '3', '6'),
('24EU08188', 'SRIRAMULA HANSIKA', '3', '6'),
('24EU08189', 'THANMYA SAI SRI KATURU', '3', '6'),
('24EU08190', 'THATAVARTHI MONIKA SRI LAKSHMI SAI KUMARI', '3', '6'),
('24EU08191', 'THOTA BINDU PRIYA', '3', '6'),
('24EU08192', 'TIRUMALASETTY YASHASWINI', '3', '6'),
('24EU08193', 'UPPALAPATI ANU SRI', '3', '6'),
('24EU08194', 'VARIKOLA KEERTHI VENKAT', '3', '6'),
('24EU08195', 'VEKANURU NAGA VENU GOPAL', '3', '6'),
('24EU08196', 'VEMPATI VENKATA PHANI MANASWINI', '3', '6'),
('24EU08197', 'VEMULAPALLI LIKHITH SAI', '3', '6'),
('24EU08198', 'VISSAMASETTY SARAVAN SAI KRISHNA', '3', '6');

-- --------------------------------------------------------

--
-- Table structure for table `svc`
--

CREATE TABLE `svc` (
  `roll_no` varchar(10) NOT NULL,
  `course_id` varchar(10) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `dept` varchar(100) DEFAULT NULL,
  `AY` varchar(20) DEFAULT NULL,
  `sem` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `svc`
--

INSERT INTO `svc` (`roll_no`, `course_id`, `section`, `dept`, `AY`, `sem`) VALUES
('24EU08002', '24IT281', '1', '6', '2025-26', 3),
('24EU08002', '24IT282', '1', '6', '2025-26', 3),
('24EU08002', '24IT283', '1', '6', '2025-26', 3),
('24EU08003', '24IT281', '1', '6', '2025-26', 3),
('24EU08003', '24IT282', '1', '6', '2025-26', 3),
('24EU08003', '24IT283', '1', '6', '2025-26', 3),
('24EU08004', '24IT281', '1', '6', '2025-26', 3),
('24EU08004', '24IT282', '1', '6', '2025-26', 3),
('24EU08004', '24IT283', '1', '6', '2025-26', 3),
('24EU08005', '24IT281', '1', '6', '2025-26', 3),
('24EU08005', '24IT282', '1', '6', '2025-26', 3),
('24EU08005', '24IT283', '1', '6', '2025-26', 3),
('24EU08006', '24IT281', '1', '6', '2025-26', 3),
('24EU08006', '24IT282', '1', '6', '2025-26', 3),
('24EU08006', '24IT283', '1', '6', '2025-26', 3),
('24EU08007', '24IT281', '1', '6', '2025-26', 3),
('24EU08007', '24IT282', '1', '6', '2025-26', 3),
('24EU08007', '24IT283', '1', '6', '2025-26', 3),
('24EU08008', '24IT281', '1', '6', '2025-26', 3),
('24EU08008', '24IT282', '1', '6', '2025-26', 3),
('24EU08008', '24IT283', '1', '6', '2025-26', 3),
('24EU08009', '24IT281', '1', '6', '2025-26', 3),
('24EU08009', '24IT282', '1', '6', '2025-26', 3),
('24EU08009', '24IT283', '1', '6', '2025-26', 3),
('24EU08010', '24IT281', '1', '6', '2025-26', 3),
('24EU08010', '24IT282', '1', '6', '2025-26', 3),
('24EU08010', '24IT283', '1', '6', '2025-26', 3),
('24EU08011', '24IT281', '1', '6', '2025-26', 3),
('24EU08011', '24IT282', '1', '6', '2025-26', 3),
('24EU08011', '24IT283', '1', '6', '2025-26', 3),
('24EU08012', '24IT281', '1', '6', '2025-26', 3),
('24EU08012', '24IT282', '1', '6', '2025-26', 3),
('24EU08012', '24IT283', '1', '6', '2025-26', 3),
('24EU08013', '24IT281', '1', '6', '2025-26', 3),
('24EU08013', '24IT282', '1', '6', '2025-26', 3),
('24EU08013', '24IT283', '1', '6', '2025-26', 3),
('24EU08014', '24IT281', '1', '6', '2025-26', 3),
('24EU08014', '24IT282', '1', '6', '2025-26', 3),
('24EU08014', '24IT283', '1', '6', '2025-26', 3),
('24EU08015', '24IT281', '1', '6', '2025-26', 3),
('24EU08015', '24IT282', '1', '6', '2025-26', 3),
('24EU08015', '24IT283', '1', '6', '2025-26', 3),
('24EU08016', '24IT281', '1', '6', '2025-26', 3),
('24EU08016', '24IT282', '1', '6', '2025-26', 3),
('24EU08016', '24IT283', '1', '6', '2025-26', 3),
('24EU08017', '24IT281', '1', '6', '2025-26', 3),
('24EU08017', '24IT282', '1', '6', '2025-26', 3),
('24EU08017', '24IT283', '1', '6', '2025-26', 3),
('24EU08018', '24IT281', '1', '6', '2025-26', 3),
('24EU08018', '24IT282', '1', '6', '2025-26', 3),
('24EU08018', '24IT283', '1', '6', '2025-26', 3),
('24EU08019', '24IT281', '1', '6', '2025-26', 3),
('24EU08019', '24IT282', '1', '6', '2025-26', 3),
('24EU08019', '24IT283', '1', '6', '2025-26', 3),
('24EU08020', '24IT281', '1', '6', '2025-26', 3),
('24EU08020', '24IT282', '1', '6', '2025-26', 3),
('24EU08020', '24IT283', '1', '6', '2025-26', 3),
('24EU08021', '24IT281', '1', '6', '2025-26', 3),
('24EU08021', '24IT282', '1', '6', '2025-26', 3),
('24EU08021', '24IT283', '1', '6', '2025-26', 3),
('24EU08022', '24IT281', '1', '6', '2025-26', 3),
('24EU08022', '24IT282', '1', '6', '2025-26', 3),
('24EU08022', '24IT283', '1', '6', '2025-26', 3),
('24EU08023', '24IT281', '1', '6', '2025-26', 3),
('24EU08023', '24IT282', '1', '6', '2025-26', 3),
('24EU08023', '24IT283', '1', '6', '2025-26', 3),
('24EU08024', '24IT281', '1', '6', '2025-26', 3),
('24EU08024', '24IT282', '1', '6', '2025-26', 3),
('24EU08024', '24IT283', '1', '6', '2025-26', 3),
('24EU08025', '24IT281', '1', '6', '2025-26', 3),
('24EU08025', '24IT282', '1', '6', '2025-26', 3),
('24EU08025', '24IT283', '1', '6', '2025-26', 3),
('24EU08026', '24IT281', '1', '6', '2025-26', 3),
('24EU08026', '24IT282', '1', '6', '2025-26', 3),
('24EU08026', '24IT283', '1', '6', '2025-26', 3),
('24EU08027', '24IT281', '1', '6', '2025-26', 3),
('24EU08027', '24IT282', '1', '6', '2025-26', 3),
('24EU08027', '24IT283', '1', '6', '2025-26', 3),
('24EU08028', '24IT281', '1', '6', '2025-26', 3),
('24EU08028', '24IT282', '1', '6', '2025-26', 3),
('24EU08028', '24IT283', '1', '6', '2025-26', 3),
('24EU08029', '24IT281', '1', '6', '2025-26', 3),
('24EU08029', '24IT282', '1', '6', '2025-26', 3),
('24EU08029', '24IT283', '1', '6', '2025-26', 3),
('24EU08030', '24IT281', '1', '6', '2025-26', 3),
('24EU08030', '24IT282', '1', '6', '2025-26', 3),
('24EU08030', '24IT283', '1', '6', '2025-26', 3),
('24EU08031', '24IT281', '1', '6', '2025-26', 3),
('24EU08031', '24IT282', '1', '6', '2025-26', 3),
('24EU08031', '24IT283', '1', '6', '2025-26', 3),
('24EU08032', '24IT281', '1', '6', '2025-26', 3),
('24EU08032', '24IT282', '1', '6', '2025-26', 3),
('24EU08032', '24IT283', '1', '6', '2025-26', 3),
('24EU08034', '24IT281', '1', '6', '2025-26', 3),
('24EU08034', '24IT282', '1', '6', '2025-26', 3),
('24EU08034', '24IT283', '1', '6', '2025-26', 3),
('24EU08035', '24IT281', '1', '6', '2025-26', 3),
('24EU08035', '24IT282', '1', '6', '2025-26', 3),
('24EU08035', '24IT283', '1', '6', '2025-26', 3),
('24EU08036', '24IT281', '1', '6', '2025-26', 3),
('24EU08036', '24IT282', '1', '6', '2025-26', 3),
('24EU08036', '24IT283', '1', '6', '2025-26', 3),
('24EU08037', '24IT281', '1', '6', '2025-26', 3),
('24EU08037', '24IT282', '1', '6', '2025-26', 3),
('24EU08037', '24IT283', '1', '6', '2025-26', 3),
('24EU08038', '24IT281', '1', '6', '2025-26', 3),
('24EU08038', '24IT282', '1', '6', '2025-26', 3),
('24EU08038', '24IT283', '1', '6', '2025-26', 3),
('24EU08039', '24IT281', '1', '6', '2025-26', 3),
('24EU08039', '24IT282', '1', '6', '2025-26', 3),
('24EU08039', '24IT283', '1', '6', '2025-26', 3),
('24EU08040', '24IT281', '1', '6', '2025-26', 3),
('24EU08040', '24IT282', '1', '6', '2025-26', 3),
('24EU08040', '24IT283', '1', '6', '2025-26', 3),
('24EU08041', '24IT281', '1', '6', '2025-26', 3),
('24EU08041', '24IT282', '1', '6', '2025-26', 3),
('24EU08041', '24IT283', '1', '6', '2025-26', 3),
('24EU08042', '24IT281', '1', '6', '2025-26', 3),
('24EU08042', '24IT282', '1', '6', '2025-26', 3),
('24EU08042', '24IT283', '1', '6', '2025-26', 3),
('24EU08043', '24IT281', '1', '6', '2025-26', 3),
('24EU08043', '24IT282', '1', '6', '2025-26', 3),
('24EU08043', '24IT283', '1', '6', '2025-26', 3),
('24EU08044', '24IT281', '1', '6', '2025-26', 3),
('24EU08044', '24IT282', '1', '6', '2025-26', 3),
('24EU08044', '24IT283', '1', '6', '2025-26', 3),
('24EU08045', '24IT281', '1', '6', '2025-26', 3),
('24EU08045', '24IT282', '1', '6', '2025-26', 3),
('24EU08045', '24IT283', '1', '6', '2025-26', 3),
('24EU08046', '24IT281', '1', '6', '2025-26', 3),
('24EU08046', '24IT282', '1', '6', '2025-26', 3),
('24EU08046', '24IT283', '1', '6', '2025-26', 3),
('24EU08047', '24IT281', '1', '6', '2025-26', 3),
('24EU08047', '24IT282', '1', '6', '2025-26', 3),
('24EU08047', '24IT283', '1', '6', '2025-26', 3),
('24EU08048', '24IT281', '1', '6', '2025-26', 3),
('24EU08048', '24IT282', '1', '6', '2025-26', 3),
('24EU08048', '24IT283', '1', '6', '2025-26', 3),
('24EU08049', '24IT281', '1', '6', '2025-26', 3),
('24EU08049', '24IT282', '1', '6', '2025-26', 3),
('24EU08049', '24IT283', '1', '6', '2025-26', 3),
('24EU08050', '24IT281', '1', '6', '2025-26', 3),
('24EU08050', '24IT282', '1', '6', '2025-26', 3),
('24EU08050', '24IT283', '1', '6', '2025-26', 3),
('24EU08051', '24IT281', '1', '6', '2025-26', 3),
('24EU08051', '24IT282', '1', '6', '2025-26', 3),
('24EU08051', '24IT283', '1', '6', '2025-26', 3),
('24EU08052', '24IT281', '1', '6', '2025-26', 3),
('24EU08052', '24IT282', '1', '6', '2025-26', 3),
('24EU08052', '24IT283', '1', '6', '2025-26', 3),
('24EU08053', '24IT281', '1', '6', '2025-26', 3),
('24EU08053', '24IT282', '1', '6', '2025-26', 3),
('24EU08053', '24IT283', '1', '6', '2025-26', 3),
('24EU08054', '24IT281', '1', '6', '2025-26', 3),
('24EU08054', '24IT282', '1', '6', '2025-26', 3),
('24EU08054', '24IT283', '1', '6', '2025-26', 3),
('24EU08055', '24IT281', '1', '6', '2025-26', 3),
('24EU08055', '24IT282', '1', '6', '2025-26', 3),
('24EU08055', '24IT283', '1', '6', '2025-26', 3),
('24EU08056', '24IT281', '1', '6', '2025-26', 3),
('24EU08056', '24IT282', '1', '6', '2025-26', 3),
('24EU08056', '24IT283', '1', '6', '2025-26', 3),
('24EU08057', '24IT281', '1', '6', '2025-26', 3),
('24EU08057', '24IT282', '1', '6', '2025-26', 3),
('24EU08057', '24IT283', '1', '6', '2025-26', 3),
('24EU08058', '24IT281', '1', '6', '2025-26', 3),
('24EU08058', '24IT282', '1', '6', '2025-26', 3),
('24EU08058', '24IT283', '1', '6', '2025-26', 3),
('24EU08059', '24IT281', '1', '6', '2025-26', 3),
('24EU08059', '24IT282', '1', '6', '2025-26', 3),
('24EU08059', '24IT283', '1', '6', '2025-26', 3),
('24EU08060', '24IT281', '1', '6', '2025-26', 3),
('24EU08060', '24IT282', '1', '6', '2025-26', 3),
('24EU08060', '24IT283', '1', '6', '2025-26', 3),
('24EU08061', '24IT281', '1', '6', '2025-26', 3),
('24EU08061', '24IT282', '1', '6', '2025-26', 3),
('24EU08061', '24IT283', '1', '6', '2025-26', 3),
('24EU08062', '24IT281', '1', '6', '2025-26', 3),
('24EU08062', '24IT282', '1', '6', '2025-26', 3),
('24EU08062', '24IT283', '1', '6', '2025-26', 3),
('24EU08063', '24IT281', '1', '6', '2025-26', 3),
('24EU08063', '24IT282', '1', '6', '2025-26', 3),
('24EU08063', '24IT283', '1', '6', '2025-26', 3),
('24EU08064', '24IT281', '1', '6', '2025-26', 3),
('24EU08064', '24IT282', '1', '6', '2025-26', 3),
('24EU08064', '24IT283', '1', '6', '2025-26', 3),
('24EU08065', '24IT281', '1', '6', '2025-26', 3),
('24EU08065', '24IT282', '1', '6', '2025-26', 3),
('24EU08065', '24IT283', '1', '6', '2025-26', 3),
('24EU08066', '24IT281', '1', '6', '2025-26', 3),
('24EU08066', '24IT282', '1', '6', '2025-26', 3),
('24EU08066', '24IT283', '1', '6', '2025-26', 3),
('24EU08067', '24IT281', '2', '6', '2025-26', 3),
('24EU08067', '24IT282', '2', '6', '2025-26', 3),
('24EU08067', '24IT283', '2', '6', '2025-26', 3),
('24EU08068', '24IT281', '2', '6', '2025-26', 3),
('24EU08068', '24IT282', '2', '6', '2025-26', 3),
('24EU08068', '24IT283', '2', '6', '2025-26', 3),
('24EU08069', '24IT281', '2', '6', '2025-26', 3),
('24EU08069', '24IT282', '2', '6', '2025-26', 3),
('24EU08069', '24IT283', '2', '6', '2025-26', 3),
('24EU08070', '24IT281', '2', '6', '2025-26', 3),
('24EU08070', '24IT282', '2', '6', '2025-26', 3),
('24EU08070', '24IT283', '2', '6', '2025-26', 3),
('24EU08071', '24IT281', '2', '6', '2025-26', 3),
('24EU08071', '24IT282', '2', '6', '2025-26', 3),
('24EU08071', '24IT283', '2', '6', '2025-26', 3),
('24EU08072', '24IT281', '2', '6', '2025-26', 3),
('24EU08072', '24IT282', '2', '6', '2025-26', 3),
('24EU08072', '24IT283', '2', '6', '2025-26', 3),
('24EU08073', '24IT281', '2', '6', '2025-26', 3),
('24EU08073', '24IT282', '2', '6', '2025-26', 3),
('24EU08073', '24IT283', '2', '6', '2025-26', 3),
('24EU08074', '24IT281', '2', '6', '2025-26', 3),
('24EU08074', '24IT282', '2', '6', '2025-26', 3),
('24EU08074', '24IT283', '2', '6', '2025-26', 3),
('24EU08075', '24IT281', '2', '6', '2025-26', 3),
('24EU08075', '24IT282', '2', '6', '2025-26', 3),
('24EU08075', '24IT283', '2', '6', '2025-26', 3),
('24EU08076', '24IT281', '2', '6', '2025-26', 3),
('24EU08076', '24IT282', '2', '6', '2025-26', 3),
('24EU08076', '24IT283', '2', '6', '2025-26', 3),
('24EU08077', '24IT281', '2', '6', '2025-26', 3),
('24EU08077', '24IT282', '2', '6', '2025-26', 3),
('24EU08077', '24IT283', '2', '6', '2025-26', 3),
('24EU08078', '24IT281', '2', '6', '2025-26', 3),
('24EU08078', '24IT282', '2', '6', '2025-26', 3),
('24EU08078', '24IT283', '2', '6', '2025-26', 3),
('24EU08079', '24IT281', '2', '6', '2025-26', 3),
('24EU08079', '24IT282', '2', '6', '2025-26', 3),
('24EU08079', '24IT283', '2', '6', '2025-26', 3),
('24EU08080', '24IT281', '2', '6', '2025-26', 3),
('24EU08080', '24IT282', '2', '6', '2025-26', 3),
('24EU08080', '24IT283', '2', '6', '2025-26', 3),
('24EU08081', '24IT281', '2', '6', '2025-26', 3),
('24EU08081', '24IT282', '2', '6', '2025-26', 3),
('24EU08081', '24IT283', '2', '6', '2025-26', 3),
('24EU08082', '24IT281', '2', '6', '2025-26', 3),
('24EU08082', '24IT282', '2', '6', '2025-26', 3),
('24EU08082', '24IT283', '2', '6', '2025-26', 3),
('24EU08083', '24IT281', '2', '6', '2025-26', 3),
('24EU08083', '24IT282', '2', '6', '2025-26', 3),
('24EU08083', '24IT283', '2', '6', '2025-26', 3),
('24EU08084', '24IT281', '2', '6', '2025-26', 3),
('24EU08084', '24IT282', '2', '6', '2025-26', 3),
('24EU08084', '24IT283', '2', '6', '2025-26', 3),
('24EU08085', '24IT281', '2', '6', '2025-26', 3),
('24EU08085', '24IT282', '2', '6', '2025-26', 3),
('24EU08085', '24IT283', '2', '6', '2025-26', 3),
('24EU08086', '24IT281', '2', '6', '2025-26', 3),
('24EU08086', '24IT282', '2', '6', '2025-26', 3),
('24EU08086', '24IT283', '2', '6', '2025-26', 3),
('24EU08087', '24IT281', '2', '6', '2025-26', 3),
('24EU08087', '24IT282', '2', '6', '2025-26', 3),
('24EU08087', '24IT283', '2', '6', '2025-26', 3),
('24EU08088', '24IT281', '2', '6', '2025-26', 3),
('24EU08088', '24IT282', '2', '6', '2025-26', 3),
('24EU08088', '24IT283', '2', '6', '2025-26', 3),
('24EU08089', '24IT281', '2', '6', '2025-26', 3),
('24EU08089', '24IT282', '2', '6', '2025-26', 3),
('24EU08089', '24IT283', '2', '6', '2025-26', 3),
('24EU08090', '24IT281', '2', '6', '2025-26', 3),
('24EU08090', '24IT282', '2', '6', '2025-26', 3),
('24EU08090', '24IT283', '2', '6', '2025-26', 3),
('24EU08091', '24IT281', '2', '6', '2025-26', 3),
('24EU08091', '24IT282', '2', '6', '2025-26', 3),
('24EU08091', '24IT283', '2', '6', '2025-26', 3),
('24EU08092', '24IT281', '2', '6', '2025-26', 3),
('24EU08092', '24IT282', '2', '6', '2025-26', 3),
('24EU08092', '24IT283', '2', '6', '2025-26', 3),
('24EU08093', '24IT281', '2', '6', '2025-26', 3),
('24EU08093', '24IT282', '2', '6', '2025-26', 3),
('24EU08093', '24IT283', '2', '6', '2025-26', 3),
('24EU08094', '24IT281', '2', '6', '2025-26', 3),
('24EU08094', '24IT282', '2', '6', '2025-26', 3),
('24EU08094', '24IT283', '2', '6', '2025-26', 3),
('24EU08095', '24IT281', '2', '6', '2025-26', 3),
('24EU08095', '24IT282', '2', '6', '2025-26', 3),
('24EU08095', '24IT283', '2', '6', '2025-26', 3),
('24EU08096', '24IT281', '2', '6', '2025-26', 3),
('24EU08096', '24IT282', '2', '6', '2025-26', 3),
('24EU08096', '24IT283', '2', '6', '2025-26', 3),
('24EU08097', '24IT281', '2', '6', '2025-26', 3),
('24EU08097', '24IT282', '2', '6', '2025-26', 3),
('24EU08097', '24IT283', '2', '6', '2025-26', 3),
('24EU08098', '24IT281', '2', '6', '2025-26', 3),
('24EU08098', '24IT282', '2', '6', '2025-26', 3),
('24EU08098', '24IT283', '2', '6', '2025-26', 3),
('24EU08099', '24IT281', '2', '6', '2025-26', 3),
('24EU08099', '24IT282', '2', '6', '2025-26', 3),
('24EU08099', '24IT283', '2', '6', '2025-26', 3),
('24EU08100', '24IT281', '2', '6', '2025-26', 3),
('24EU08100', '24IT282', '2', '6', '2025-26', 3),
('24EU08100', '24IT283', '2', '6', '2025-26', 3),
('24EU08101', '24IT281', '2', '6', '2025-26', 3),
('24EU08101', '24IT282', '2', '6', '2025-26', 3),
('24EU08101', '24IT283', '2', '6', '2025-26', 3),
('24EU08102', '24IT281', '2', '6', '2025-26', 3),
('24EU08102', '24IT282', '2', '6', '2025-26', 3),
('24EU08102', '24IT283', '2', '6', '2025-26', 3),
('24EU08103', '24IT281', '2', '6', '2025-26', 3),
('24EU08103', '24IT282', '2', '6', '2025-26', 3),
('24EU08103', '24IT283', '2', '6', '2025-26', 3),
('24EU08104', '24IT281', '2', '6', '2025-26', 3),
('24EU08104', '24IT282', '2', '6', '2025-26', 3),
('24EU08104', '24IT283', '2', '6', '2025-26', 3),
('24EU08105', '24IT281', '2', '6', '2025-26', 3),
('24EU08105', '24IT282', '2', '6', '2025-26', 3),
('24EU08105', '24IT283', '2', '6', '2025-26', 3),
('24EU08106', '24IT281', '2', '6', '2025-26', 3),
('24EU08106', '24IT282', '2', '6', '2025-26', 3),
('24EU08106', '24IT283', '2', '6', '2025-26', 3),
('24EU08107', '24IT281', '2', '6', '2025-26', 3),
('24EU08107', '24IT282', '2', '6', '2025-26', 3),
('24EU08107', '24IT283', '2', '6', '2025-26', 3),
('24EU08108', '24IT281', '2', '6', '2025-26', 3),
('24EU08108', '24IT282', '2', '6', '2025-26', 3),
('24EU08108', '24IT283', '2', '6', '2025-26', 3),
('24EU08109', '24IT281', '2', '6', '2025-26', 3),
('24EU08109', '24IT282', '2', '6', '2025-26', 3),
('24EU08109', '24IT283', '2', '6', '2025-26', 3),
('24EU08110', '24IT281', '2', '6', '2025-26', 3),
('24EU08110', '24IT282', '2', '6', '2025-26', 3),
('24EU08110', '24IT283', '2', '6', '2025-26', 3),
('24EU08111', '24IT281', '2', '6', '2025-26', 3),
('24EU08111', '24IT282', '2', '6', '2025-26', 3),
('24EU08111', '24IT283', '2', '6', '2025-26', 3),
('24EU08112', '24IT281', '2', '6', '2025-26', 3),
('24EU08112', '24IT282', '2', '6', '2025-26', 3),
('24EU08112', '24IT283', '2', '6', '2025-26', 3),
('24EU08113', '24IT281', '2', '6', '2025-26', 3),
('24EU08113', '24IT282', '2', '6', '2025-26', 3),
('24EU08113', '24IT283', '2', '6', '2025-26', 3),
('24EU08114', '24IT281', '2', '6', '2025-26', 3),
('24EU08114', '24IT282', '2', '6', '2025-26', 3),
('24EU08114', '24IT283', '2', '6', '2025-26', 3),
('24EU08115', '24IT281', '2', '6', '2025-26', 3),
('24EU08115', '24IT282', '2', '6', '2025-26', 3),
('24EU08115', '24IT283', '2', '6', '2025-26', 3),
('24EU08116', '24IT281', '2', '6', '2025-26', 3),
('24EU08116', '24IT282', '2', '6', '2025-26', 3),
('24EU08116', '24IT283', '2', '6', '2025-26', 3),
('24EU08117', '24IT281', '2', '6', '2025-26', 3),
('24EU08117', '24IT282', '2', '6', '2025-26', 3),
('24EU08117', '24IT283', '2', '6', '2025-26', 3),
('24EU08118', '24IT281', '2', '6', '2025-26', 3),
('24EU08118', '24IT282', '2', '6', '2025-26', 3),
('24EU08118', '24IT283', '2', '6', '2025-26', 3),
('24EU08119', '24IT281', '2', '6', '2025-26', 3),
('24EU08119', '24IT282', '2', '6', '2025-26', 3),
('24EU08119', '24IT283', '2', '6', '2025-26', 3),
('24EU08120', '24IT281', '2', '6', '2025-26', 3),
('24EU08120', '24IT282', '2', '6', '2025-26', 3),
('24EU08120', '24IT283', '2', '6', '2025-26', 3),
('24EU08121', '24IT281', '2', '6', '2025-26', 3),
('24EU08121', '24IT282', '2', '6', '2025-26', 3),
('24EU08121', '24IT283', '2', '6', '2025-26', 3),
('24EU08122', '24IT281', '2', '6', '2025-26', 3),
('24EU08122', '24IT282', '2', '6', '2025-26', 3),
('24EU08122', '24IT283', '2', '6', '2025-26', 3),
('24EU08123', '24IT281', '2', '6', '2025-26', 3),
('24EU08123', '24IT282', '2', '6', '2025-26', 3),
('24EU08123', '24IT283', '2', '6', '2025-26', 3),
('24EU08124', '24IT281', '2', '6', '2025-26', 3),
('24EU08124', '24IT282', '2', '6', '2025-26', 3),
('24EU08124', '24IT283', '2', '6', '2025-26', 3),
('24EU08125', '24IT281', '2', '6', '2025-26', 3),
('24EU08125', '24IT282', '2', '6', '2025-26', 3),
('24EU08125', '24IT283', '2', '6', '2025-26', 3),
('24EU08126', '24IT281', '2', '6', '2025-26', 3),
('24EU08126', '24IT282', '2', '6', '2025-26', 3),
('24EU08126', '24IT283', '2', '6', '2025-26', 3),
('24EU08127', '24IT281', '2', '6', '2025-26', 3),
('24EU08127', '24IT282', '2', '6', '2025-26', 3),
('24EU08127', '24IT283', '2', '6', '2025-26', 3),
('24EU08128', '24IT281', '2', '6', '2025-26', 3),
('24EU08128', '24IT282', '2', '6', '2025-26', 3),
('24EU08128', '24IT283', '2', '6', '2025-26', 3),
('24EU08129', '24IT281', '2', '6', '2025-26', 3),
('24EU08129', '24IT282', '2', '6', '2025-26', 3),
('24EU08129', '24IT283', '2', '6', '2025-26', 3),
('24EU08130', '24IT281', '2', '6', '2025-26', 3),
('24EU08130', '24IT282', '2', '6', '2025-26', 3),
('24EU08130', '24IT283', '2', '6', '2025-26', 3),
('24EU08131', '24IT281', '2', '6', '2025-26', 3),
('24EU08131', '24IT282', '2', '6', '2025-26', 3),
('24EU08131', '24IT283', '2', '6', '2025-26', 3),
('24EU08132', '24IT281', '2', '6', '2025-26', 3),
('24EU08132', '24IT282', '2', '6', '2025-26', 3),
('24EU08132', '24IT283', '2', '6', '2025-26', 3),
('24EU08133', '24IT281', '3', '6', '2025-26', 3),
('24EU08133', '24IT282', '3', '6', '2025-26', 3),
('24EU08133', '24IT283', '3', '6', '2025-26', 3),
('24EU08134', '24IT281', '3', '6', '2025-26', 3),
('24EU08134', '24IT282', '3', '6', '2025-26', 3),
('24EU08134', '24IT283', '3', '6', '2025-26', 3),
('24EU08135', '24IT281', '3', '6', '2025-26', 3),
('24EU08135', '24IT282', '3', '6', '2025-26', 3),
('24EU08135', '24IT283', '3', '6', '2025-26', 3),
('24EU08136', '24IT281', '3', '6', '2025-26', 3),
('24EU08136', '24IT282', '3', '6', '2025-26', 3),
('24EU08136', '24IT283', '3', '6', '2025-26', 3),
('24EU08137', '24IT281', '3', '6', '2025-26', 3),
('24EU08137', '24IT282', '3', '6', '2025-26', 3),
('24EU08137', '24IT283', '3', '6', '2025-26', 3),
('24EU08138', '24IT281', '3', '6', '2025-26', 3),
('24EU08138', '24IT282', '3', '6', '2025-26', 3),
('24EU08138', '24IT283', '3', '6', '2025-26', 3),
('24EU08139', '24IT281', '3', '6', '2025-26', 3),
('24EU08139', '24IT282', '3', '6', '2025-26', 3),
('24EU08139', '24IT283', '3', '6', '2025-26', 3),
('24EU08140', '24IT281', '3', '6', '2025-26', 3),
('24EU08140', '24IT282', '3', '6', '2025-26', 3),
('24EU08140', '24IT283', '3', '6', '2025-26', 3),
('24EU08141', '24IT281', '3', '6', '2025-26', 3),
('24EU08141', '24IT282', '3', '6', '2025-26', 3),
('24EU08141', '24IT283', '3', '6', '2025-26', 3),
('24EU08142', '24IT281', '3', '6', '2025-26', 3),
('24EU08142', '24IT282', '3', '6', '2025-26', 3),
('24EU08142', '24IT283', '3', '6', '2025-26', 3),
('24EU08143', '24IT281', '3', '6', '2025-26', 3),
('24EU08143', '24IT282', '3', '6', '2025-26', 3),
('24EU08143', '24IT283', '3', '6', '2025-26', 3),
('24EU08144', '24IT281', '3', '6', '2025-26', 3),
('24EU08144', '24IT282', '3', '6', '2025-26', 3),
('24EU08144', '24IT283', '3', '6', '2025-26', 3),
('24EU08145', '24IT281', '3', '6', '2025-26', 3),
('24EU08145', '24IT282', '3', '6', '2025-26', 3),
('24EU08145', '24IT283', '3', '6', '2025-26', 3),
('24EU08146', '24IT281', '3', '6', '2025-26', 3),
('24EU08146', '24IT282', '3', '6', '2025-26', 3),
('24EU08146', '24IT283', '3', '6', '2025-26', 3),
('24EU08147', '24IT281', '3', '6', '2025-26', 3),
('24EU08147', '24IT282', '3', '6', '2025-26', 3),
('24EU08147', '24IT283', '3', '6', '2025-26', 3),
('24EU08148', '24IT281', '3', '6', '2025-26', 3),
('24EU08148', '24IT282', '3', '6', '2025-26', 3),
('24EU08148', '24IT283', '3', '6', '2025-26', 3),
('24EU08149', '24IT281', '3', '6', '2025-26', 3),
('24EU08149', '24IT282', '3', '6', '2025-26', 3),
('24EU08149', '24IT283', '3', '6', '2025-26', 3),
('24EU08150', '24IT281', '3', '6', '2025-26', 3),
('24EU08150', '24IT282', '3', '6', '2025-26', 3),
('24EU08150', '24IT283', '3', '6', '2025-26', 3),
('24EU08151', '24IT281', '3', '6', '2025-26', 3),
('24EU08151', '24IT282', '3', '6', '2025-26', 3),
('24EU08151', '24IT283', '3', '6', '2025-26', 3),
('24EU08152', '24IT281', '3', '6', '2025-26', 3),
('24EU08152', '24IT282', '3', '6', '2025-26', 3),
('24EU08152', '24IT283', '3', '6', '2025-26', 3),
('24EU08153', '24IT281', '3', '6', '2025-26', 3),
('24EU08153', '24IT282', '3', '6', '2025-26', 3),
('24EU08153', '24IT283', '3', '6', '2025-26', 3),
('24EU08154', '24IT281', '3', '6', '2025-26', 3),
('24EU08154', '24IT282', '3', '6', '2025-26', 3),
('24EU08154', '24IT283', '3', '6', '2025-26', 3),
('24EU08155', '24IT281', '3', '6', '2025-26', 3),
('24EU08155', '24IT282', '3', '6', '2025-26', 3),
('24EU08155', '24IT283', '3', '6', '2025-26', 3),
('24EU08156', '24IT281', '3', '6', '2025-26', 3),
('24EU08156', '24IT282', '3', '6', '2025-26', 3),
('24EU08156', '24IT283', '3', '6', '2025-26', 3),
('24EU08157', '24IT281', '3', '6', '2025-26', 3),
('24EU08157', '24IT282', '3', '6', '2025-26', 3),
('24EU08157', '24IT283', '3', '6', '2025-26', 3),
('24EU08158', '24IT281', '3', '6', '2025-26', 3),
('24EU08158', '24IT282', '3', '6', '2025-26', 3),
('24EU08158', '24IT283', '3', '6', '2025-26', 3),
('24EU08159', '24IT281', '3', '6', '2025-26', 3),
('24EU08159', '24IT282', '3', '6', '2025-26', 3),
('24EU08159', '24IT283', '3', '6', '2025-26', 3),
('24EU08160', '24IT281', '3', '6', '2025-26', 3),
('24EU08160', '24IT282', '3', '6', '2025-26', 3),
('24EU08160', '24IT283', '3', '6', '2025-26', 3),
('24EU08161', '24IT281', '3', '6', '2025-26', 3),
('24EU08161', '24IT282', '3', '6', '2025-26', 3),
('24EU08161', '24IT283', '3', '6', '2025-26', 3),
('24EU08162', '24IT281', '3', '6', '2025-26', 3),
('24EU08162', '24IT282', '3', '6', '2025-26', 3),
('24EU08162', '24IT283', '3', '6', '2025-26', 3),
('24EU08163', '24IT281', '3', '6', '2025-26', 3),
('24EU08163', '24IT282', '3', '6', '2025-26', 3),
('24EU08163', '24IT283', '3', '6', '2025-26', 3),
('24EU08164', '24IT281', '3', '6', '2025-26', 3),
('24EU08164', '24IT282', '3', '6', '2025-26', 3),
('24EU08164', '24IT283', '3', '6', '2025-26', 3),
('24EU08165', '24IT281', '3', '6', '2025-26', 3),
('24EU08165', '24IT282', '3', '6', '2025-26', 3),
('24EU08165', '24IT283', '3', '6', '2025-26', 3),
('24EU08166', '24IT281', '3', '6', '2025-26', 3),
('24EU08166', '24IT282', '3', '6', '2025-26', 3),
('24EU08166', '24IT283', '3', '6', '2025-26', 3),
('24EU08167', '24IT281', '3', '6', '2025-26', 3),
('24EU08167', '24IT282', '3', '6', '2025-26', 3),
('24EU08167', '24IT283', '3', '6', '2025-26', 3),
('24EU08168', '24IT281', '3', '6', '2025-26', 3),
('24EU08168', '24IT282', '3', '6', '2025-26', 3),
('24EU08168', '24IT283', '3', '6', '2025-26', 3),
('24EU08169', '24IT281', '3', '6', '2025-26', 3),
('24EU08169', '24IT282', '3', '6', '2025-26', 3),
('24EU08169', '24IT283', '3', '6', '2025-26', 3),
('24EU08170', '24IT281', '3', '6', '2025-26', 3),
('24EU08170', '24IT282', '3', '6', '2025-26', 3),
('24EU08170', '24IT283', '3', '6', '2025-26', 3),
('24EU08171', '24IT281', '3', '6', '2025-26', 3),
('24EU08171', '24IT282', '3', '6', '2025-26', 3),
('24EU08171', '24IT283', '3', '6', '2025-26', 3),
('24EU08172', '24IT281', '3', '6', '2025-26', 3),
('24EU08172', '24IT282', '3', '6', '2025-26', 3),
('24EU08172', '24IT283', '3', '6', '2025-26', 3),
('24EU08173', '24IT281', '3', '6', '2025-26', 3),
('24EU08173', '24IT282', '3', '6', '2025-26', 3),
('24EU08173', '24IT283', '3', '6', '2025-26', 3),
('24EU08174', '24IT281', '3', '6', '2025-26', 3),
('24EU08174', '24IT282', '3', '6', '2025-26', 3),
('24EU08174', '24IT283', '3', '6', '2025-26', 3),
('24EU08175', '24IT281', '3', '6', '2025-26', 3),
('24EU08175', '24IT282', '3', '6', '2025-26', 3),
('24EU08175', '24IT283', '3', '6', '2025-26', 3),
('24EU08176', '24IT281', '3', '6', '2025-26', 3),
('24EU08176', '24IT282', '3', '6', '2025-26', 3),
('24EU08176', '24IT283', '3', '6', '2025-26', 3),
('24EU08177', '24IT281', '3', '6', '2025-26', 3),
('24EU08177', '24IT282', '3', '6', '2025-26', 3),
('24EU08177', '24IT283', '3', '6', '2025-26', 3),
('24EU08178', '24IT281', '3', '6', '2025-26', 3),
('24EU08178', '24IT282', '3', '6', '2025-26', 3),
('24EU08178', '24IT283', '3', '6', '2025-26', 3),
('24EU08179', '24IT281', '3', '6', '2025-26', 3),
('24EU08179', '24IT282', '3', '6', '2025-26', 3),
('24EU08179', '24IT283', '3', '6', '2025-26', 3),
('24EU08180', '24IT281', '3', '6', '2025-26', 3),
('24EU08180', '24IT282', '3', '6', '2025-26', 3),
('24EU08180', '24IT283', '3', '6', '2025-26', 3),
('24EU08181', '24IT281', '3', '6', '2025-26', 3),
('24EU08181', '24IT282', '3', '6', '2025-26', 3),
('24EU08181', '24IT283', '3', '6', '2025-26', 3),
('24EU08182', '24IT281', '3', '6', '2025-26', 3),
('24EU08182', '24IT282', '3', '6', '2025-26', 3),
('24EU08182', '24IT283', '3', '6', '2025-26', 3),
('24EU08183', '24IT281', '3', '6', '2025-26', 3),
('24EU08183', '24IT282', '3', '6', '2025-26', 3),
('24EU08183', '24IT283', '3', '6', '2025-26', 3),
('24EU08184', '24IT281', '3', '6', '2025-26', 3),
('24EU08184', '24IT282', '3', '6', '2025-26', 3),
('24EU08184', '24IT283', '3', '6', '2025-26', 3),
('24EU08185', '24IT281', '3', '6', '2025-26', 3),
('24EU08185', '24IT282', '3', '6', '2025-26', 3),
('24EU08185', '24IT283', '3', '6', '2025-26', 3),
('24EU08186', '24IT281', '3', '6', '2025-26', 3),
('24EU08186', '24IT282', '3', '6', '2025-26', 3),
('24EU08186', '24IT283', '3', '6', '2025-26', 3),
('24EU08187', '24IT281', '3', '6', '2025-26', 3),
('24EU08187', '24IT282', '3', '6', '2025-26', 3),
('24EU08187', '24IT283', '3', '6', '2025-26', 3),
('24EU08188', '24IT281', '3', '6', '2025-26', 3),
('24EU08188', '24IT282', '3', '6', '2025-26', 3),
('24EU08188', '24IT283', '3', '6', '2025-26', 3),
('24EU08189', '24IT281', '3', '6', '2025-26', 3),
('24EU08189', '24IT282', '3', '6', '2025-26', 3),
('24EU08189', '24IT283', '3', '6', '2025-26', 3),
('24EU08190', '24IT281', '3', '6', '2025-26', 3),
('24EU08190', '24IT282', '3', '6', '2025-26', 3),
('24EU08190', '24IT283', '3', '6', '2025-26', 3),
('24EU08191', '24IT281', '3', '6', '2025-26', 3),
('24EU08191', '24IT282', '3', '6', '2025-26', 3),
('24EU08191', '24IT283', '3', '6', '2025-26', 3),
('24EU08192', '24IT281', '3', '6', '2025-26', 3),
('24EU08192', '24IT282', '3', '6', '2025-26', 3),
('24EU08192', '24IT283', '3', '6', '2025-26', 3),
('24EU08193', '24IT281', '3', '6', '2025-26', 3),
('24EU08193', '24IT282', '3', '6', '2025-26', 3),
('24EU08193', '24IT283', '3', '6', '2025-26', 3),
('24EU08194', '24IT281', '3', '6', '2025-26', 3),
('24EU08194', '24IT282', '3', '6', '2025-26', 3),
('24EU08194', '24IT283', '3', '6', '2025-26', 3),
('24EU08195', '24IT281', '3', '6', '2025-26', 3),
('24EU08195', '24IT282', '3', '6', '2025-26', 3),
('24EU08195', '24IT283', '3', '6', '2025-26', 3),
('24EU08196', '24IT281', '3', '6', '2025-26', 3),
('24EU08196', '24IT282', '3', '6', '2025-26', 3),
('24EU08196', '24IT283', '3', '6', '2025-26', 3),
('24EU08197', '24IT281', '3', '6', '2025-26', 3),
('24EU08197', '24IT282', '3', '6', '2025-26', 3),
('24EU08197', '24IT283', '3', '6', '2025-26', 3),
('24EU08198', '24IT281', '3', '6', '2025-26', 3),
('24EU08198', '24IT282', '3', '6', '2025-26', 3),
('24EU08198', '24IT283', '3', '6', '2025-26', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `fvc`
--
ALTER TABLE `fvc`
  ADD PRIMARY KEY (`course_id`,`section`,`dept`,`AY`),
  ADD KEY `fk_fvc_programme` (`programme_id`);

--
-- Indexes for table `fvc_schedule`
--
ALTER TABLE `fvc_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `course_id` (`course_id`,`section`,`dept`,`AY`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`roll_no`,`course_id`,`section`,`dept`,`AY`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `programmes`
--
ALTER TABLE `programmes`
  ADD PRIMARY KEY (`programme_id`),
  ADD UNIQUE KEY `unique_programme_per_dept` (`programme_name`,`dept_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `fk_programmes_dept` (`dept_id`);

--
-- Indexes for table `remuneration`
--
ALTER TABLE `remuneration`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`school_id`),
  ADD UNIQUE KEY `school_name` (`school_name`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`roll_no`);

--
-- Indexes for table `svc`
--
ALTER TABLE `svc`
  ADD PRIMARY KEY (`roll_no`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `fvc_schedule`
--
ALTER TABLE `fvc_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `programmes`
--
ALTER TABLE `programmes`
  MODIFY `programme_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `remuneration`
--
ALTER TABLE `remuneration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `school_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`);

--
-- Constraints for table `fvc`
--
ALTER TABLE `fvc`
  ADD CONSTRAINT `fk_fvc_programme` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`programme_id`),
  ADD CONSTRAINT `fvc_ibfk_1` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`programme_id`);

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`roll_no`) REFERENCES `student` (`roll_no`),
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- Constraints for table `programmes`
--
ALTER TABLE `programmes`
  ADD CONSTRAINT `fk_programmes_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `programmes_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`),
  ADD CONSTRAINT `programmes_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- Constraints for table `svc`
--
ALTER TABLE `svc`
  ADD CONSTRAINT `svc_ibfk_1` FOREIGN KEY (`roll_no`) REFERENCES `student` (`roll_no`),
  ADD CONSTRAINT `svc_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
