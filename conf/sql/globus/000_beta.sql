-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 11, 2013 at 02:14 PM
-- Server version: 5.1.69
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `globus`
--

-- --------------------------------------------------------

--
-- Table structure for table `gbtest`
--

CREATE TABLE IF NOT EXISTS `gbtest` (
  `gbtestpk` int(11) NOT NULL AUTO_INCREMENT,
  `rank` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `mail_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gbtest_chapterfk` int(11) NOT NULL,
  `esa` tinyint(1) NOT NULL,
  PRIMARY KEY (`gbtestpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;

--
-- Dumping data for table `gbtest`
--

INSERT INTO `gbtest` (`gbtestpk`, `rank`, `name`, `content`, `mail_to`, `gbtest_chapterfk`, `esa`) VALUES
(1, 1, 'Singapore branch request', 'Assignment brief. Singapore branch request.', 'george@bbc.com', 1, 0),
(2, 2, 'Transfert request', 'It''s lucky you seem to get on well enough with your new boss Derek Straw, because you suddenly find you are in an urgent situation and you need to request his help. Your client meeting is running over time, and you won''t be able to make it back to the office until later this evening.<br /><br />\r\nProblem is you were planning to finish the HongKong Presentation preparation by the 5:00 deadline. So now you have to ask Derek. <br /><br />He''ll have to add two names to the presentation guest list because Karen Lee (who is always great to have at these presentations because she always asks lots of good questions in Q & A) from CyberWand Corp. and Abdul Habib from Indo Biz Inc. both just called to confirm their attendance. <br /><br />Derek will also need to obtain a PDF map of your new\r\noffice location in HK, and he''ll also have to switch the font on the PowerPoint slides from Times to\r\nArial.  This is pretty urgent, so you''ll want a confir mation that he can do these things either phoned or emailed to your cell phone (090.5555.1212).\r\n', 'dan@newsoftheworld.co.uk', 1, 0),
(3, 3, 'Accept an invitation', 'Assignment brief. Accept an invitation.', 'bill@nbc.co.uk', 2, 0),
(4, 4, 'Combination: info + request', 'Assignment brief. Info + Request.', 'stan@bcmedia.com', 2, 0),
(5, 1, 'Long Week End', ' It has been two years since your company’s computer system has been upgraded.  But over this weekend, which is a long weekend running from Saturday May 21 to Monday May 23, the IT department is going to upgrade all of your company’s computers.  IT are going to do the installation from noon on Sunday to 8:00 p.m. on Monday.  Without exception, no one will be allowed to use computers during that time.  Your job is to send out a company-wide message informing everyone of the situation and reminding them to close all files and applications on their computers when they leave on Friday so they don’t lose any data.', 'stan@bcmedia.com', 3, 1),
(6, 2, 'Ask Derek', 'It''s lucky you seem to get on well enough with your new boss Derek Straw, because you suddenly find you are in an urgent situation and you need to request his help. Your client meeting is running over time, and you won''t be able to make it back to the office until later this evening.<br /><br />\r\nProblem is you were planning to finish the HongKong Presentation preparation by the 5:00 deadline. So now you have to ask Derek. <br /><br />He''ll have to add two names to the presentation guest list because Karen Lee (who is always great to have at these presentations because she always asks lots of good questions in Q & A) from CyberWand Corp. and Abdul Habib from Indo Biz Inc. both just called to confirm their attendance. <br /><br />Derek will also need to obtain a PDF map of your new\r\noffice location in HK, and he''ll also have to switch the font on the PowerPoint slides from Times to\r\nArial.  This is pretty urgent, so you''ll want a confir mation that he can do these things either phoned or emailed to your cell phone (090.5555.1212).\r\n', 'dan@newsoftheworld.co.uk', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `gbtest_answer`
--

CREATE TABLE IF NOT EXISTS `gbtest_answer` (
  `gbtest_answerpk` int(11) NOT NULL AUTO_INCREMENT,
  `gbtestfk` int(11) NOT NULL,
  `mail_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mail_title_html` text COLLATE utf8_unicode_ci NOT NULL,
  `mail_content` text COLLATE utf8_unicode_ci NOT NULL,
  `mail_content_html` text COLLATE utf8_unicode_ci NOT NULL,
  `gbuserfk` int(11) NOT NULL,
  `status` enum('draft','sent','returned') COLLATE utf8_unicode_ci NOT NULL,
  `date_submitted` datetime NOT NULL,
  `last_update` datetime NOT NULL,
  `date_returned` datetime NOT NULL,
  `date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gbtest_answerpk`),
  UNIQUE KEY `gbtestfk` (`gbtestfk`,`gbuserfk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- Table structure for table `gbtest_chapter`
--

CREATE TABLE IF NOT EXISTS `gbtest_chapter` (
  `gbtest_chapterpk` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY (`gbtest_chapterpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

--
-- Dumping data for table `gbtest_chapter`
--

INSERT INTO `gbtest_chapter` (`gbtest_chapterpk`, `name`, `rank`) VALUES
(1, 'Requests', 1),
(2, 'Information', 2),
(3, 'ESA1', 3),
(4, 'ESA2', 4);

-- --------------------------------------------------------

--
-- Table structure for table `gbtest_chapter_group`
--

CREATE TABLE IF NOT EXISTS `gbtest_chapter_group` (
  `gbtest_chapter_grouppk` int(11) NOT NULL AUTO_INCREMENT,
  `gbtest_chapterfk` int(11) NOT NULL,
  `gbuser_groupfk` int(11) NOT NULL,
  `deadline` date NOT NULL,
  PRIMARY KEY (`gbtest_chapter_grouppk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=140 ;

-- --------------------------------------------------------

--
-- Table structure for table `gbtest_correction`
--

CREATE TABLE IF NOT EXISTS `gbtest_correction` (
  `gbtest_correctionpk` int(11) NOT NULL AUTO_INCREMENT,
  `gbtest_answerfk` int(11) NOT NULL,
  `corrected_by` int(11) NOT NULL,
  `date_send` datetime NOT NULL,
  `good` tinyint(4) NOT NULL DEFAULT '-1',
  `date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('draft','sent') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`gbtest_correctionpk`),
  UNIQUE KEY `gbtest_answerfk` (`gbtest_answerfk`,`corrected_by`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Table structure for table `gbtest_correction_point`
--

CREATE TABLE IF NOT EXISTS `gbtest_correction_point` (
  `gbtest_correction_pointpk` int(11) NOT NULL AUTO_INCREMENT,
  `gbtest_correctionfk` int(11) NOT NULL,
  `comment` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `importance` int(11) NOT NULL,
  `type` enum('tone','phrases','layout','logic','language') COLLATE utf8_unicode_ci NOT NULL,
  `start` int(11) NOT NULL,
  `end` int(11) NOT NULL,
  PRIMARY KEY (`gbtest_correction_pointpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Table structure for table `gbtest_esa_score`
--

CREATE TABLE IF NOT EXISTS `gbtest_esa_score` (
  `gbtest_esa_scorepk` int(11) NOT NULL AUTO_INCREMENT,
  `gbtest_answerfk` int(11) NOT NULL,
  `corrected_by` int(11) NOT NULL,
  `date_send` datetime NOT NULL,
  `date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('draft','sent') COLLATE utf8_unicode_ci NOT NULL,
  `tone` float NOT NULL,
  `phrases` float NOT NULL,
  `layout` float NOT NULL,
  `logic` float NOT NULL,
  `language` float NOT NULL,
  `total` float NOT NULL,
  PRIMARY KEY (`gbtest_esa_scorepk`),
  UNIQUE KEY `gbtest_answerfk` (`gbtest_answerfk`,`corrected_by`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `gbtest_esa_score_detail`
--

CREATE TABLE IF NOT EXISTS `gbtest_esa_score_detail` (
  `gbtest_esa_score_detailpk` int(11) NOT NULL AUTO_INCREMENT,
  `gbtest_esa_scorefk` int(11) NOT NULL,
  `gbtest_esa_skillfk` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  PRIMARY KEY (`gbtest_esa_score_detailpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=687 ;

-- --------------------------------------------------------

--
-- Table structure for table `gbtest_esa_skill`
--

CREATE TABLE IF NOT EXISTS `gbtest_esa_skill` (
  `gbtest_esa_skillpk` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `skill` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `importance` float DEFAULT NULL,
  `condition_good` varchar(29) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition_average` varchar(24) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition_bad` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text_good` varchar(114) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text_average` varchar(146) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text_bad` varchar(139) COLLATE utf8_unicode_ci DEFAULT NULL,
  `valmin` int(11) NOT NULL DEFAULT '0',
  `valmax` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`gbtest_esa_skillpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=37 ;

--
-- Dumping data for table `gbtest_esa_skill`
--

INSERT INTO `gbtest_esa_skill` (`gbtest_esa_skillpk`, `category`, `skill`, `importance`, `condition_good`, `condition_average`, `condition_bad`, `text_good`, `text_average`, `text_bad`, `valmin`, `valmax`) VALUES
(1, 'logic', 'Overall Logic Structure ', 6, 'Always correct overall struct', '1 Overall Struct Error', '> 1 Overall Struct Error', 'Used the most logical method to structure message', 'Select the most logical method to structure message', 'Before beginning, clarify purpose of message so that you will choose the correct logical method to structure message', 0, 2),
(2, 'logic', 'Main Point', 13, 'Always main pt 1st', 'Main pt not 1st once', 'Main pt not 1st >once', 'Introduced main point(s) as early as logically possible', 'Review logic structure to make sure you introduce main point at earliest possible point', 'Introduce main point at earliest possible point; details, if necessary, follow later', 0, 2),
(3, 'logic', 'Logical Order', 5, 'Always correct order', '1 order error', '> 1 order errors', 'Organized remaining information in logical order', 'Organize non-main point information in logical order ', 'Identify non-main point(s) information and organize in logical order', 0, 4),
(4, 'logic', 'Message Brevity', 3, 'No unnec. Info', '1 -2 unnec. Info', '> 2 unnec. Info', 'Included only necessary information', 'Include only necessary information', 'Include only information that clearly helps achieve the purpose of your message', 0, 3),
(5, 'logic', 'Message Completeness', 13, 'Inc. all nec. Info', 'Missed 1 nec. Info', 'missed > 1 nec. Info', 'Included all necessary information', 'Include all necessary information', 'Try to include all necessary information by reviewing your purpose and considering readers more', 0, 4),
(6, 'phrases', 'Standard Business Phrasing', 8, '> 5 std bus. Phrases', '3 - 5 std bus. Phrases', '0 - 2 std bus. Phrases', 'Used variety of standard business phrases', 'Use more of a variety of standard business phrases', 'Review and use standard business phrases', 0, 6),
(7, 'phrases', 'Natural Phrasing', 4, 'No unnat/trans phrase', '1 - 2 unnat/trans phrase', '> 2 unnat/trans phrase', 'Used no unnatural, awkward or translated phrases', 'Avoid using any unnatural, awkward or translated phrases', 'Try not to use any unnatural, awkward or translated phrases', 0, 2),
(8, 'phrases', 'Transitions', 3, '> 5 transitions', '3 - 5 transitions', '0 - 2 transitions', 'Added appropriate transitions where needed', 'Use more and more appropriate transitions where needed', 'Review, use and/or use more transitions (e.g. Also, Next, However)', 0, 4),
(9, 'phrases', 'Vocabulary', 3, 'No vocab errors', '1 - 2 vocab errors', '> 2 vocab errors', 'Used understandable vocabulary', 'Use more simple and clear vocabulary  ', 'Expand overall vocabulary and use simple and clear vocabulary', 0, 3),
(10, 'phrases', 'Wordiness', 2, 'No excess words/phrases', '1 - 2 excess words/phrs', '> 2 excess words/phrs', 'Used no excess words ', 'Omit excess words which do not clarify or are not needed ', 'Try to remove any excess words or phrases to improve clarity', 0, 4),
(11, 'tone', 'Strength: Maximum', 6, 'Never too strong', '1 too strong', '> 1 too strong', 'Message was never too strong/direct', 'Be more careful to avoid using tone that is too strong/direct by considering situation and relationship with reader', 'Avoid using tone that is too strong/direct by considering situation and relationship with reader', 0, 4),
(12, 'tone', 'Strength: Minimum', 6, 'Never too soft/indirect', '1 too soft/indirect', '> 1 too soft/indirect', 'Messsage was never too soft/indirect', 'Be more careful to avoid using tone that is too soft/indirect by considering situation and relationship with reader', 'Avoid using tone that is too soft/indirect by considering situation and relationship with reader', 0, 2),
(13, 'tone', 'Formality: Maximum', 3, 'Never too formal', '1 too formal', '> 1 too formal', 'Message was never too formal', 'Be more careful to avoid using tone that is too formal by considering situation and relationship with reader', 'Avoid using tone that is too formal by considering situation and relationship with reader', 0, 2),
(14, 'tone', 'Formality: Minimum', 3, 'Never too casual', '1 too casual', '> 1 too casual', 'Message was never too casual', 'Try to avoid using tone that is too casual by considering situation and relationship with reader', 'Avoid using tone that is too casual by considering situation and relationship with reader', 0, 2),
(15, 'tone', 'Greeting/Comp Closing Formality: Max', 1, 'G/C never too formal', 'G/C 1 too formal', 'G/C >1 too formal', 'Greeting & Complimentary Closing were never too formal ', 'Always select a Greeting and/or Complimentary Closing not too formal considering the relationship with the reader(s)', 'Don''t select a Greeting and/or Complimentary Closing too formal considering the relationship with the reader(s)', 0, 2),
(16, 'tone', 'Greeting/Comp Closing Formality: Min', 1, 'G/C never too casual', 'G/C 1 too casual', 'G/C > 1 too casual', 'Greeting & Complimentary Closing were never too casual ', 'Always select a Greeting and/or Complimentary Closing not too casual considering the relationship with the reader(s)', 'Don''t select a Greeting and/or Complimentary Closing too formal considering the relationship with the reader(s)', 0, 2),
(17, 'layout', 'Subject Line Length', 1, 'SubLine always < 5 wds', 'SubLine once > 4 wds', 'SubLine >once >4wds', 'Limited Subject Lines to 4 words or fewer when possible', 'Try to limit Subject Lines to 4 words if possible by removing articles, prepositions, pronouns and unnecessary words ', 'Use shorter Subject Lines by removing articles, prepositions, pronouns and unnecessary words', 0, 2),
(18, 'layout', 'Subject Line Clarity', 1, 'No SubLine clarity errors', '1 SubLine clarity error', '>1 SubLine clarity errors', 'Provided clear Subject Lines', 'Clearly identify your message by adding the topic and/or theme in the Subject Line', 'Provide clear Subject Lines that relate to your topic', 0, 2),
(19, 'layout', 'Layout Tools', 2.5, 'No Layout tool errors/omis.', '1 Layout tool err/omis', '>1 Layout tool err/omis', 'Used correct layout tools for highlighting key points', 'Use more layout tools (numbers, headings, bold, etc.) when needed to highlight information', 'Use layout tools (numbers, headings,bold, etc.) when needed to highlight information', 0, 2),
(20, 'layout', 'Sentence Spacing', 0.5, 'No Sent Space errors', '1 Sent space error', '>1 Sent space errors', 'Inserted correct number of spaces between sentences', 'Always insert correct number of spaces before and after punctuation marks (e.g. type two spaces at the end of a sentence)', 'Insert correct number of spaces before and after punctuation marks (e.g. type two spaces at the end of a sentence)', 0, 2),
(21, 'layout', 'Section Spacing', 0.5, 'No Sect Space errors', '1 Sect Space error', '>1 Sect Space errors', 'Inserted correct number of blank lines between message sections (i.e. Greeting, Paragraphs, Complimentary Closing)', 'Always insert one blank line between message sections (e.g. after Greeting, between Paragraphs, between final paragraph and Complimentary Closing)', 'Insert one blank line between message sections (e.g. after Greeting, between Paragraphs, between final paragraph and Complimentary Closing)', 0, 2),
(22, 'layout', 'Paragraph Size', 1, 'No Paragraph size errors', '1 paragraph size error', '>1 paragraph size errors', 'Used correct number of paragraphs with 2-4 sentences/paragraph', 'More consistently limit paragraphs to 2-4 sentences/paragraph', 'Limit paragraphs to 2-4 sentences/paragraph', 0, 2),
(23, 'layout', 'Paragraph Grouping', 0.5, 'No Parag. Group errors', '1 parag. Group error', '>1 parag. Group errors', 'Grouped sentences into approporiate paragraphs', 'Always group related sentences into the same paragraph', 'Try to group related sentences into the same paragraph', 0, 2),
(24, 'layout', 'Paragraph Style', 1, 'No. Parag. Indent errors', '1 Parag. Indent error', '>1 parag. Indent errors', 'Started paragraphs at left margin with no indenting', 'Always use Block Style paragraphs with no indenting', 'Use Block Style paragraphs with no indenting', 0, 2),
(25, 'layout', 'Punctuation', 0.5, 'No Punctuation errors', '1 - 2 punctuation errors', '>2 punctuation errors', 'Applied professional punctuation', 'Review and use more advanced punctuation', 'Review and use correct punctuation, particularly commas and periods', 0, 3),
(26, 'layout', 'Capitalization', 0.5, 'No Capitalization errors', '1 Capitalization error', '> 1 Capitalization errors', 'Capitalized subject lines, headings and appropriate text', 'Always capitalize appropriate text, headings and Subject Lines', 'Review capitalization and be sure to capitalize appropriate text, headings and Subject Lines', 0, 2),
(27, 'layout', 'Spelling', 1, 'No Spelling errors', '1 - 2 spelling errors', '> 2 spelling errors', 'Correctly spelled all words', 'Always review spelling and use SpellCheck', 'Review spelling and use SpellCheck', 0, 3),
(28, 'language', 'Sentence Length', 1, 'No sent. Length errors', '1 - 2 Sent. Too long', '> 2 sent. Too long', 'Limited sentences to 20 words maximum', 'Always use short sentences limited to 20 words maximum', 'Try to use short sentences limited to 20 words maximum', 0, 3),
(29, 'language', 'Sentence Order', 1.5, 'No syntax errors', '1 - 2 syntax errors', '> 2 syntax errors', 'Organized sentence elements in correct order', 'Double-check syntax (sentence order)', 'Review and use correct syntax (sentence order)', 0, 3),
(30, 'language', 'Verb Use', 2.5, 'No verb use errors', '1 verb use error', '> 1 verb use errors', 'No verb use errors', 'Double-check verb use', 'Review verbs and use correctly', 0, 2),
(31, 'language', 'Articles', 1, 'No article errors', '1 article error', '> 1 article errors', 'No article errors', 'Double-check article use (a, an, the)', 'Review articles (a, an, the) and use correctly', 0, 2),
(32, 'language', 'Pronouns', 0.5, 'No pronoun errors', '1 pronoun error', '> 1 pronoun errors', 'No pronoun errors', 'Double-check pronoun use (he, she, etc.) ', 'Review pronouns (he, she, etc.) and use correctly', 0, 2),
(33, 'language', 'Relative Clauses', 0.5, 'No rel. clause errors', '1 rel. clause error', '> 1 rel. clause errors', 'No relative clause errors', 'Double-check relative clause use', 'Review relative clauses and use correctly', 0, 2),
(34, 'language', 'Adjectives & Adverbs', 0.5, 'No adj/adv errors', '1 adj/adv error', '> 1 adj/adv errors', 'No adjective or adverb errors', 'Double-check adjective and adverb use', 'Review adjectives and adverbs and use correctly', 0, 2),
(35, 'language', 'Prepositions', 2, 'No prepositn errors', '1 prepositn error', '> 1 prepositn errors', 'No preposition errors', 'Double-check preposition use (to, by, on, etc.) ', 'Review prepositions (to, by, on, etc.) and use correctly', 0, 2),
(36, 'language', 'Plurality', 0.5, 'No plurality errors', '1 plurality error', '> 1 plurality errors', 'No plurality errors', 'Double-check plurality', 'Review plurality and use correctly', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `gbuser`
--

CREATE TABLE IF NOT EXISTS `gbuser` (
  `gbuserpk` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('student','hrmanager','teacher','gbadmin') COLLATE utf8_unicode_ci NOT NULL,
  `loginfk` int(11) NOT NULL,
  `gbuser_companyfk` int(11) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gbuserpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=76 ;

--
-- Dumping data for table `gbuser`
--

INSERT INTO `gbuser` (`gbuserpk`, `type`, `loginfk`, `gbuser_companyfk`, `created_on`) VALUES
(72, 'student', 112, 5, '2013-11-11 03:37:41'),
(73, 'hrmanager', 113, 5, '2013-11-11 03:40:50'),
(74, 'teacher', 114, 5, '2013-11-11 03:41:36'),
(75, 'student', 115, 5, '2013-11-11 04:54:21');

-- --------------------------------------------------------

--
-- Table structure for table `gbuser_company`
--

CREATE TABLE IF NOT EXISTS `gbuser_company` (
  `gbuser_companypk` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `industryfk` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gbuser_companypk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

--
-- Dumping data for table `gbuser_company`
--

INSERT INTO `gbuser_company` (`gbuser_companypk`, `name`, `industryfk`, `active`, `created_on`) VALUES
(5, 'BC Media', 4, 1, '2013-11-11 03:31:02');

-- --------------------------------------------------------

--
-- Table structure for table `gbuser_group`
--

CREATE TABLE IF NOT EXISTS `gbuser_group` (
  `gbuser_grouppk` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gbuser_companyfk` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gbuser_grouppk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=18 ;

--
-- Dumping data for table `gbuser_group`
--

INSERT INTO `gbuser_group` (`gbuser_grouppk`, `name`, `gbuser_companyfk`, `active`, `created_on`) VALUES
(17, 'IT Team', 5, 1, '2013-11-11 03:37:53');

-- --------------------------------------------------------

--
-- Table structure for table `gbuser_group_member`
--

CREATE TABLE IF NOT EXISTS `gbuser_group_member` (
  `gbuser_group_memberpk` int(11) NOT NULL AUTO_INCREMENT,
  `gbuser_groupfk` int(11) NOT NULL,
  `gbuserfk` int(11) NOT NULL,
  PRIMARY KEY (`gbuser_group_memberpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=266 ;

--
-- Dumping data for table `gbuser_group_member`
--

INSERT INTO `gbuser_group_member` (`gbuser_group_memberpk`, `gbuser_groupfk`, `gbuserfk`) VALUES
(265, 17, 74),
(264, 17, 75),
(263, 17, 72),
(262, 17, 73);

-- --------------------------------------------------------

--
-- Table structure for table `industry`
--

CREATE TABLE IF NOT EXISTS `industry` (
  `industrypk` int(11) NOT NULL AUTO_INCREMENT,
  `industry_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `industry_desc` text COLLATE utf8_unicode_ci NOT NULL,
  `parentfk` int(11) NOT NULL,
  PRIMARY KEY (`industrypk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=58 ;

--
-- Dumping data for table `industry`
--

INSERT INTO `industry` (`industrypk`, `industry_name`, `industry_desc`, `parentfk`) VALUES
(1, 'Advertising Agencies ', '', 0),
(2, 'Air Transportation', '', 0),
(3, 'Alcohol', '', 0),
(4, 'Art ', '', 0),
(5, 'Automotive', '', 0),
(6, 'Beauty & Spa', '', 0),
(7, 'Books & Magazines', '', 0),
(8, 'Business Consulting', '', 0),
(9, 'Clubs', '', 0),
(10, 'Cosmetics ', '', 0),
(11, 'Cruising and Yachts ', '', 0),
(12, 'Education ', '', 0),
(13, 'Electronic & Appliances ', '', 0),
(14, 'Energy', '', 0),
(15, 'Events', '', 0),
(16, 'Executive Search & Recruit', '', 0),
(17, 'Fashion ', '', 0),
(18, 'Finance', '', 0),
(19, 'Food & Drink ', '', 0),
(20, 'Freelance', '', 0),
(21, 'Furniture and Interiors ', '', 0),
(22, 'Gardening', '', 0),
(23, 'Graphic Design', '', 0),
(24, 'Government & Embassies ', '', 0),
(25, 'Health Care', '', 0),
(26, 'Hotels', '', 0),
(27, 'Household goods ', '', 0),
(28, 'Industrialized products', '', 0),
(29, 'Insurance', '', 0),
(30, 'Internet', '', 0),
(31, 'IT', '', 0),
(32, 'Journalist', '', 0),
(33, 'Jewelry and Accessories', '', 0),
(34, 'Legal', '', 0),
(35, 'Luxury Goods', '', 0),
(36, 'Marketing Agencies ', '', 0),
(37, 'Media', '', 0),
(38, 'Music & Entertainment', '', 0),
(39, 'NPO/NGO', '', 0),
(40, 'Outdoor ', '', 0),
(41, 'Pets', '', 0),
(42, 'PR Agencies', '', 0),
(43, 'Printing Services', '', 0),
(44, 'Real Estate', '', 0),
(45, 'Retail Centers', '', 0),
(46, 'Restaurants & Bars', '', 0),
(47, 'Shipping & Logistics', '', 0),
(48, 'Sports ', '', 0),
(49, 'Stationary Goods', '', 0),
(50, 'Supermarket', '', 0),
(51, 'Theme Parks ', '', 0),
(52, 'Toys & kids', '', 0),
(53, 'Trade House', '', 0),
(54, 'Translation & Proof Reading', '', 0),
(55, 'Transportation Services', '', 0),
(56, 'Travel Agencies', '', 0),
(57, 'Wedding Consulting & Services', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE IF NOT EXISTS `login` (
  `loginpk` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `pseudo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` int(11) NOT NULL,
  `courtesy` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `position` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_ext` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `teamfk` int(11) NOT NULL COMMENT '1: sales, 2:It, 3:Manag, 4:Prod, 5:Admin, 6:other ',
  `is_admin` tinyint(1) NOT NULL,
  `valid_status` int(11) NOT NULL,
  `hashcode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_create` datetime NOT NULL,
  `date_update` datetime DEFAULT NULL,
  `date_expire` datetime DEFAULT NULL,
  `date_reset` datetime DEFAULT NULL,
  `date_last_log` datetime DEFAULT NULL,
  `log_hash` text COLLATE utf8_unicode_ci NOT NULL,
  `webmail` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `webpassword` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mailport` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Imap` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `aliasName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `signature` text COLLATE utf8_unicode_ci NOT NULL,
  `date_passwd_changed` datetime NOT NULL,
  `otherloginfks` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`loginpk`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=116 ;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`loginpk`, `id`, `password`, `pseudo`, `birthdate`, `gender`, `courtesy`, `email`, `lastname`, `firstname`, `position`, `phone`, `phone_ext`, `status`, `teamfk`, `is_admin`, `valid_status`, `hashcode`, `date_create`, `date_update`, `date_expire`, `date_reset`, `date_last_log`, `log_hash`, `webmail`, `webpassword`, `mailport`, `Imap`, `aliasName`, `signature`, `date_passwd_changed`, `otherloginfks`) VALUES
(34, 'gbadmin', 'gbadmin', 'gbadmin', NULL, 0, '', 'gbadmin@globusjapan.com', 'Admin', 'Globus', 'GB Admin', '', '', 1, 0, 0, 0, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, '2013-11-11 14:12:35', 'de6bc31182b3f69455e4f0207911cc8cfd583000', '', '', '', '', '', '', '0000-00-00 00:00:00', '114,113,112,115'),
(114, 'trainer', 'trainer', 'trainer', NULL, 0, '', 'trainer@globusjapan.com', 'Trainer', 'Trainer', 'Teacher', NULL, '', 1, 0, 0, 0, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, '2013-11-11 13:53:27', '', '', '', '', '', '', '', '0000-00-00 00:00:00', '113,112,34,115'),
(113, 'hrmanager', 'hrmanager', 'hrmanager', NULL, 0, '', 'hrmanager@bcmedia.fr', 'Manager', 'HR', 'Hrmanager', NULL, '', 1, 0, 0, 0, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, '2013-11-11 13:53:15', '', '', '', '', '', '', '', '0000-00-00 00:00:00', '34,112,114,115'),
(112, 'student', 'student', 'student', NULL, 0, '', 'student@bcmedia.fr', 'Student', 'Student', 'Student', NULL, '', 1, 0, 0, 0, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, '2013-11-11 13:53:37', '', '', '', '', '', '', '', '0000-00-00 00:00:00', '113,114,34,115'),
(115, 'student2', 'student2', 'student2', NULL, 0, '', 'student@student2.com', 'Student2', 'Student', 'Student', NULL, '', 1, 0, 0, 0, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, '2013-11-11 13:56:27', '', '', '', '', '', '', '', '0000-00-00 00:00:00', '114,113,34,112');

-- --------------------------------------------------------

--
-- Table structure for table `login_access_history`
--

CREATE TABLE IF NOT EXISTS `login_access_history` (
  `login_access_historypk` int(11) NOT NULL AUTO_INCREMENT,
  `history` text COLLATE utf8_unicode_ci,
  `ip_address` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `loginfk` int(11) NOT NULL,
  `date_start` datetime NOT NULL,
  `nb_page` int(11) NOT NULL,
  `session_uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`login_access_historypk`),
  KEY `loginfk` (`loginfk`,`session_uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=22 ;

--
-- Dumping data for table `login_access_history`
--

INSERT INTO `login_access_history` (`login_access_historypk`, `history`, `ip_address`, `loginfk`, `date_start`, `nb_page`, `session_uid`) VALUES
(1, NULL, '127.0.0.1', 34, '2013-11-11 12:22:43', 87, 'sess_52804d83c3d381.82496662'),
(2, NULL, '127.0.0.1', 34, '2013-11-11 12:45:05', 5, 'sess_528052c177c5a5.50191985'),
(3, NULL, '127.0.0.1', 113, '2013-11-11 12:45:14', 3, 'sess_528052ca5bdf22.70537051'),
(4, NULL, '127.0.0.1', 112, '2013-11-11 12:45:20', 3, 'sess_528052d01264b7.11459215'),
(5, NULL, '127.0.0.1', 113, '2013-11-11 12:45:25', 3, 'sess_528052d59f1111.05106326'),
(6, NULL, '127.0.0.1', 114, '2013-11-11 12:45:31', 11, 'sess_528052dbc8d915.65959009'),
(7, NULL, '127.0.0.1', 34, '2013-11-11 12:47:06', 34, 'sess_5280533a1dbae3.43621262'),
(8, NULL, '127.0.0.1', 113, '2013-11-11 13:25:48', 3, 'sess_52805c4cd83799.73386827'),
(9, NULL, '127.0.0.1', 114, '2013-11-11 13:25:57', 3, 'sess_52805c55b04366.18748476'),
(10, NULL, '127.0.0.1', 113, '2013-11-11 13:26:04', 3, 'sess_52805c5c5b3581.25695891'),
(11, NULL, '127.0.0.1', 34, '2013-11-11 13:26:09', 25, 'sess_52805c61644004.23296197'),
(12, NULL, '127.0.0.1', 34, '2013-11-11 13:47:55', 8, 'sess_5280617b1164a2.90697095'),
(13, NULL, '127.0.0.1', 112, '2013-11-11 13:48:22', 16, 'sess_528061961138d0.17063323'),
(14, NULL, '127.0.0.1', 113, '2013-11-11 13:53:15', 5, 'sess_528062bbdc48e8.63878267'),
(15, NULL, '127.0.0.1', 114, '2013-11-11 13:53:27', 7, 'sess_528062c788d838.49802183'),
(16, NULL, '127.0.0.1', 112, '2013-11-11 13:53:37', 3, 'sess_528062d1bd1505.40576974'),
(17, NULL, '127.0.0.1', 34, '2013-11-11 13:53:46', 13, 'sess_528062dab3d9a6.98802657'),
(18, NULL, '127.0.0.1', 115, '2013-11-11 13:55:52', 9, 'sess_52806358468753.27020803'),
(19, NULL, '127.0.0.1', 34, '2013-11-11 13:56:06', 6, 'sess_52806366e2a4d2.10351488'),
(20, NULL, '127.0.0.1', 115, '2013-11-11 13:56:27', 3, 'sess_5280637b39e847.86460840'),
(21, NULL, '127.0.0.1', 34, '2013-11-11 13:56:28', 29, 'sess_5280637ccc5297.54955215');

-- --------------------------------------------------------

--
-- Table structure for table `login_activity`
--

CREATE TABLE IF NOT EXISTS `login_activity` (
  `login_activitypk` int(11) NOT NULL AUTO_INCREMENT,
  `loginfk` int(11) NOT NULL,
  `cp_uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cp_action` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cp_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cp_pk` int(11) NOT NULL,
  `text` text COLLATE utf8_unicode_ci,
  `data` text COLLATE utf8_unicode_ci,
  `log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `log_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `followerfk` int(11) DEFAULT NULL COMMENT 'Pk of Contact ',
  `status` int(11) NOT NULL COMMENT '0-Not Visited,1-Visited',
  `notifierfk` int(11) DEFAULT NULL COMMENT 'person to notify',
  `sentemail` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`login_activitypk`),
  KEY `loginfk` (`loginfk`),
  KEY `followerfk` (`followerfk`),
  KEY `notifierfk` (`notifierfk`),
  KEY `sentemail` (`sentemail`),
  KEY `log_date` (`log_date`),
  KEY `cp_uid` (`cp_uid`(60),`cp_action`(60),`cp_type`(60),`cp_pk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `login_group`
--

CREATE TABLE IF NOT EXISTS `login_group` (
  `login_grouppk` int(11) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `system` int(11) NOT NULL,
  `visible` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`login_grouppk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

--
-- Dumping data for table `login_group`
--

INSERT INTO `login_group` (`login_grouppk`, `shortname`, `title`, `system`, `visible`) VALUES
(1, 'student', 'Students', 1, 1),
(2, 'hrmanager', 'HR managers', 1, 1),
(3, 'teacher', 'Teachers', 1, 1),
(4, 'gbadmin', 'Globus Admin', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `login_group_member`
--

CREATE TABLE IF NOT EXISTS `login_group_member` (
  `login_group_memberpk` int(11) NOT NULL AUTO_INCREMENT,
  `login_groupfk` int(11) NOT NULL,
  `loginfk` int(11) NOT NULL,
  PRIMARY KEY (`login_group_memberpk`),
  UNIQUE KEY `login_groupfk` (`login_groupfk`,`loginfk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=37 ;

--
-- Dumping data for table `login_group_member`
--

INSERT INTO `login_group_member` (`login_group_memberpk`, `login_groupfk`, `loginfk`) VALUES
(4, 4, 34),
(34, 2, 113),
(36, 1, 115),
(33, 1, 112),
(35, 3, 114);

-- --------------------------------------------------------

--
-- Table structure for table `login_system_history`
--

CREATE TABLE IF NOT EXISTS `login_system_history` (
  `login_system_historypk` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `userfk` int(11) NOT NULL,
  `action` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `component` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cp_uid` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cp_action` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cp_type` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cp_pk` int(11) DEFAULT NULL,
  `uri` text COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`login_system_historypk`),
  KEY `userfk` (`userfk`),
  KEY `cp_uid` (`cp_uid`),
  KEY `cp_action` (`cp_action`),
  KEY `cp_type` (`cp_type`),
  KEY `cp_pk` (`cp_pk`),
  KEY `component` (`component`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
/*!50100 PARTITION BY RANGE ( login_system_historypk)
(PARTITION p0 VALUES LESS THAN (250000) ENGINE = MyISAM,
 PARTITION p1 VALUES LESS THAN (500000) ENGINE = MyISAM,
 PARTITION p2 VALUES LESS THAN (750000) ENGINE = MyISAM,
 PARTITION p3 VALUES LESS THAN (1000000) ENGINE = MyISAM,
 PARTITION p4 VALUES LESS THAN (1250000) ENGINE = MyISAM,
 PARTITION p5 VALUES LESS THAN (1500000) ENGINE = MyISAM,
 PARTITION p6 VALUES LESS THAN MAXVALUE ENGINE = MyISAM) */ AUTO_INCREMENT=10 ;

--
-- Dumping data for table `login_system_history`
--

INSERT INTO `login_system_history` (`login_system_historypk`, `date`, `userfk`, `action`, `component`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `uri`, `value`) VALUES
(1, '2013-11-11 13:54:21', 34, 'insert in login', '196-001_ppasa_gbad_0', '196-001', 'ppasa', 'gbad', 0, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppasa&ppt=gbad&ppk=0&datatype=student&pg=ajx', 'array (\n  ''email'' => ''student@student2.com'',\n  ''firstname'' => ''Student'',\n  ''lastname'' => ''Student2'',\n  ''position'' => ''Student'',\n  ''status'' => 1,\n  ''password'' => ''jraw3666ZQST-!##'',\n  ''id'' => ''student2st'',\n  ''pk'' => 115,\n)'),
(2, '2013-11-11 13:54:21', 34, 'insert in login_group_member', '196-001_ppasa_gbad_0', '196-001', 'ppasa', 'gbad', 0, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppasa&ppt=gbad&ppk=0&datatype=student&pg=ajx', 'array (\n  ''login_groupfk'' => 1,\n  ''loginfk'' => 115,\n  ''pk'' => 36,\n)'),
(3, '2013-11-11 13:54:21', 34, 'insert in gbuser', '196-001_ppasa_gbad_0', '196-001', 'ppasa', 'gbad', 0, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppasa&ppt=gbad&ppk=0&datatype=student&pg=ajx', 'array (\n  ''loginfk'' => 115,\n  ''gbuser_companyfk'' => 5,\n  ''type'' => ''student'',\n  ''pk'' => 75,\n)'),
(4, '2013-11-11 13:54:21', 34, 'insert in gbuser_group_member', '196-001_ppasa_gbad_0', '196-001', 'ppasa', 'gbad', 0, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppasa&ppt=gbad&ppk=0&datatype=student&pg=ajx', 'array (\n  ''gbuserfk'' => 75,\n  ''gbuser_groupfk'' => 17,\n  ''pk'' => 261,\n)'),
(5, '2013-11-11 13:54:23', 34, 'update gbuser_group', '196-001_ppase_gbad_17', '196-001', 'ppase', 'gbad', 17, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppase&ppt=gbad&ppk=17&datatype=group&pg=ajx', 'array (\n  ''name'' => ''IT Team'',\n  ''active'' => 1,\n  ''gbuser_grouppk'' => 17,\n)'),
(6, '2013-11-11 13:54:23', 34, 'delete on gbuser_group_member', '196-001_ppase_gbad_17', '196-001', 'ppase', 'gbad', 17, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppase&ppt=gbad&ppk=17&datatype=group&pg=ajx', 'array (\n  ''fk'' => 17,\n)'),
(7, '2013-11-11 13:54:23', 34, 'insert in gbuser_group_member', '196-001_ppase_gbad_17', '196-001', 'ppase', 'gbad', 17, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppase&ppt=gbad&ppk=17&datatype=group&pg=ajx', 'array (\n  ''gbuserfk'' => \n  array (\n    0 => 73,\n    1 => 72,\n    2 => 75,\n    3 => 74,\n  ),\n  ''gbuser_groupfk'' => \n  array (\n    0 => 17,\n    1 => 17,\n    2 => 17,\n    3 => 17,\n  ),\n  ''pk'' => 262,\n)'),
(8, '2013-11-11 13:54:23', 34, 'delete on gbtest_chapter_group', '196-001_ppase_gbad_17', '196-001', 'ppase', 'gbad', 17, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppase&ppt=gbad&ppk=17&datatype=group&pg=ajx', 'array (\n  ''fk'' => 17,\n)'),
(9, '2013-11-11 13:54:23', 34, 'insert in gbtest_chapter_group', '196-001_ppase_gbad_17', '196-001', 'ppase', 'gbad', 17, 'https://globus.dev.com/index.php5?uid=196-001&ppa=ppase&ppt=gbad&ppk=17&datatype=group&pg=ajx', 'array (\n  ''gbtest_chapterfk'' => \n  array (\n    0 => 1,\n    1 => 2,\n    2 => 3,\n    3 => 4,\n  ),\n  ''gbuser_groupfk'' => \n  array (\n    0 => 17,\n    1 => 17,\n    2 => 17,\n    3 => 17,\n  ),\n  ''deadline'' => \n  array (\n    0 => ''2013-11-13'',\n    1 => ''2013-11-20'',\n    2 => ''2013-11-27'',\n    3 => ''2013-12-04'',\n  ),\n  ''pk'' => 136,\n)');

-- --------------------------------------------------------

--
-- Table structure for table `manageable_list`
--

CREATE TABLE IF NOT EXISTS `manageable_list` (
  `manageable_listpk` int(11) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `cp_uid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cp_action` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cp_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cp_pk` int(255) DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8 NOT NULL,
  `description` text CHARACTER SET utf8,
  `item_type` varchar(255) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`manageable_listpk`),
  KEY `cp_uid` (`cp_uid`),
  KEY `cp_action` (`cp_action`),
  KEY `cp_pk` (`cp_pk`),
  KEY `cp_type` (`cp_type`),
  KEY `shortname` (`shortname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `manageable_list_item`
--

CREATE TABLE IF NOT EXISTS `manageable_list_item` (
  `manageable_list_itempk` int(11) NOT NULL AUTO_INCREMENT,
  `manageable_listfk` int(11) NOT NULL,
  `label` text CHARACTER SET utf8 NOT NULL,
  `value` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`manageable_list_itempk`),
  KEY `manageable_listfk` (`manageable_listfk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `right`
--

CREATE TABLE IF NOT EXISTS `right` (
  `rightpk` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `cp_uid` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `cp_action` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `cp_type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `cp_pk` int(11) NOT NULL,
  `data` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`rightpk`),
  KEY `cp_uid` (`cp_uid`),
  KEY `cp_action` (`cp_action`),
  KEY `cp_type` (`cp_type`),
  KEY `cp_uid_2` (`cp_uid`,`cp_action`,`cp_type`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=10007 ;

--
-- Dumping data for table `right`
--

INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `cp_uid`, `cp_action`, `cp_type`, `cp_pk`, `data`) VALUES
(8, 'Login static access', 'Allow to access login', 'static', '579-704', 'ppav', '', 0, NULL),
(10002, 'Student rights', 'All views, forms, and listing for the students.', 'right', '196-002', '', 'stud', 0, NULL),
(10003, 'Teacher rights', 'All views, forms, and listing for the teachers.', 'right', '196-002', '', 'teac', 0, NULL),
(115, 'Access to the contact sheet', '', 'logged', '579-704', 'ppal', 'usr', 0, NULL),
(116, 'Reset password', '', 'static', '579-704', 'ppares', 'pswd', 0, NULL),
(117, 'Redirection to login form', '', 'static', '579-704', '', 'restricted', 0, NULL),
(118, 'Logout', '', 'logged', '579-704', 'ppalgt', '', 0, NULL),
(119, 'Relog', '', 'static', '579-704', 'relog', '', 0, NULL),
(120, 'Password management', '', 'static', '579-704', 'ppasen', 'pswd', 0, NULL),
(121, 'Password management', '', 'static', '579-704', 'ppase', 'pswd', 0, NULL),
(122, 'Password management', '', 'static', '579-704', 'ppava', 'pswd', 0, NULL),
(124, 'Allow searching users', 'Let users use the field to pickup other users', 'logged', '579-704', 'ppasea', 'usr', 0, NULL),
(10004, 'HR Manager rights', 'All views, forms, and listing for the HR managers.', 'right', '196-002', '', 'hrmn', 0, NULL),
(10005, 'GB Admin rights', 'All views, forms, and listing related to the users managment for the GB Admins.', 'right', '196-001', '', 'gbad', 0, NULL),
(10006, 'GB Admin rights', 'All views, forms, and listing related to the tests supervision for the GB Admins.', 'right', '196-002', '', 'gbad', 0, NULL),
(134, 'View user preference page', 'View user preference page.', 'alias', '665-544', 'ppal', 'usrprf', 0, NULL),
(135, 'Save preference', 'Save preference.', 'alias', '665-544', 'ppasc', 'usrprf', 0, NULL),
(10001, 'Basic User Rights', 'Rights to any kind of authorized user : save preferences. Reload password.', 'right', '', '', '', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `right_tree`
--

CREATE TABLE IF NOT EXISTS `right_tree` (
  `right_treepk` int(11) NOT NULL AUTO_INCREMENT,
  `rightfk` int(11) NOT NULL,
  `parentfk` int(11) NOT NULL,
  PRIMARY KEY (`right_treepk`),
  UNIQUE KEY `rightfk_2` (`rightfk`,`parentfk`),
  KEY `rightfk` (`rightfk`),
  KEY `parentfk` (`parentfk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Dumping data for table `right_tree`
--

INSERT INTO `right_tree` (`right_treepk`, `rightfk`, `parentfk`) VALUES
(1, 134, 10001),
(2, 135, 10001);

-- --------------------------------------------------------

--
-- Table structure for table `right_user`
--

CREATE TABLE IF NOT EXISTS `right_user` (
  `right_userpk` int(11) NOT NULL AUTO_INCREMENT,
  `rightfk` int(11) NOT NULL,
  `loginfk` int(11) NOT NULL,
  `groupfk` int(11) NOT NULL,
  `template` tinyint(1) NOT NULL DEFAULT '1',
  `callback` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `callback_params` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`right_userpk`),
  KEY `rightfk` (`rightfk`),
  KEY `loginfk` (`loginfk`),
  KEY `groupfk` (`groupfk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=10 ;

--
-- Dumping data for table `right_user`
--

INSERT INTO `right_user` (`right_userpk`, `rightfk`, `loginfk`, `groupfk`, `template`, `callback`, `callback_params`) VALUES
(1, 10002, 0, 1, 0, '', ''),
(2, 10003, 0, 3, 0, '', ''),
(3, 10004, 0, 2, 0, '', ''),
(4, 10005, 0, 4, 0, '', ''),
(5, 10006, 0, 4, 0, '', ''),
(6, 10001, 0, 1, 0, '', ''),
(7, 10001, 0, 2, 0, '', ''),
(8, 10001, 0, 3, 0, '', ''),
(9, 10001, 0, 4, 0, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `settingspk` int(11) NOT NULL AUTO_INCREMENT,
  `fieldname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `fieldtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `options` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`settingspk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=393 ;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`settingspk`, `fieldname`, `fieldtype`, `options`, `value`, `description`) VALUES
(1, 'css', 'text', '', 'bcm.css', 'Name of the css of the website'),
(2, 'meta_tags', 'text', '', 'globus, online training, online coaching', 'Meta tags and description for website'),
(5, 'meta_desc', 'text', '', 'globus master', 'Meta description for website'),
(6, 'title', 'text', '', 'Globus', 'Title of the website'),
(7, 'logo', 'image', '', '', 'Website logo'),
(8, 'sitename', 'text', '', 'Globus', 'Site Name '),
(9, 'site_email', 'text', '', 'info@globusjapan.com', 'Site Email Address'),
(65, 'record_number', 'select', 'a:5:{i:10;s:2:"10";i:25;s:2:"25";i:50;s:2:"50";i:100;s:3:"100";i:200;s:3:"200";}', '25', 'Number of records you wish to display on list pages.'),
(56, 'allowed_ip', 'textarea', NULL, '127.0.0.1,192.168.215,192.168.81,1.113.56.42,118.243.81.245,118.243.81.246,118.243.81.248,183.77.226.168', 'IP allowed to access the website.'),
(50, 'password_validity', 'select', 'a:5:{s:10:"+100 years";s:6:"always";s:8:"+1 month";s:7:"1 month";s:9:"+3 months";s:8:"3 months";s:9:"+6 months";s:8:"6 months";s:7:"+1 year";s:6:"1 year";}', '-1 month', 'The time before passwords need to be changed');

-- --------------------------------------------------------

--
-- Table structure for table `settings_user`
--

CREATE TABLE IF NOT EXISTS `settings_user` (
  `settings_userpk` int(11) NOT NULL AUTO_INCREMENT,
  `loginfk` int(11) NOT NULL,
  `settingsfk` int(11) NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`settings_userpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=25 ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `shared_login`
--
CREATE TABLE IF NOT EXISTS `shared_login` (
`loginpk` int(11)
,`pseudo` varchar(255)
,`birthdate` date
,`gender` int(11)
,`courtesy` varchar(32)
,`email` varchar(256)
,`lastname` varchar(255)
,`firstname` varchar(255)
,`fullname` varchar(511)
,`phone` varchar(255)
,`phone_ext` varchar(255)
,`status` int(11)
,`teamfk` int(11)
,`is_admin` tinyint(1)
,`friendly` varchar(255)
);
-- --------------------------------------------------------

--
-- Table structure for table `version`
--

CREATE TABLE IF NOT EXISTS `version` (
  `versionpk` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_version` datetime DEFAULT NULL,
  PRIMARY KEY (`versionpk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;

--
-- Dumping data for table `version`
--

INSERT INTO `version` (`versionpk`, `version`, `date_version`) VALUES
(1, 'beta', '2013-11-11 00:00:00');

-- --------------------------------------------------------

--
-- Structure for view `shared_login`
--
DROP TABLE IF EXISTS `shared_login`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `shared_login` AS select `login`.`loginpk` AS `loginpk`,`login`.`pseudo` AS `pseudo`,`login`.`birthdate` AS `birthdate`,`login`.`gender` AS `gender`,`login`.`courtesy` AS `courtesy`,`login`.`email` AS `email`,`login`.`lastname` AS `lastname`,`login`.`firstname` AS `firstname`,concat(`login`.`firstname`,' ',`login`.`lastname`) AS `fullname`,`login`.`phone` AS `phone`,`login`.`phone_ext` AS `phone_ext`,`login`.`status` AS `status`,`login`.`teamfk` AS `teamfk`,`login`.`is_admin` AS `is_admin`,if((length(`login`.`pseudo`) > 0),`login`.`pseudo`,`login`.`firstname`) AS `friendly` from `login`;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
