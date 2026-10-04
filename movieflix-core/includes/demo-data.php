<?php
/**
 * Demo content — majority real Indian (Telugu / Hindi / other) titles with
 * official TMDB poster & backdrop paths so images match the movies.
 * Sample video URLs are public Blender / Mux streams (replace in wp-admin).
 *
 * Movie row: title, year, runtime, genres[], lang, country, rating, quality,
 *            desc, cast_idx[], director_idx, imdb, flags[], video [, poster, backdrop]
 * Series row: title, year, genres[], lang, country, rating, quality, desc,
 *             episodes[[s,e,title,video]], cast[], director, imdb, flags[, poster, backdrop]
 *
 * @package MovieFlixCore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$b   = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/';
$hls = 'https://test-streams.mux.dev/x36xhztz/x36xhztz.m3u8';

return array(
	'people' => array(
		'Prabhas', 'Allu Arjun', 'Yash', 'Ram Charan', 'Jr NTR',
		'Aamir Khan', 'Ranbir Kapoor', 'Alia Bhatt', 'Deepika Padukone', 'Rashmika Mandanna',
		'S.S. Rajamouli', 'Nag Ashwin', 'Sukumar', 'Prashanth Neel', 'Nitesh Tiwari',
		'Rajkumar Hirani', 'Sandeep Reddy Vanga', 'Hanu Raghavapudi', 'Vijay Deverakonda', 'Shahid Kapoor',
		'Mahesh Babu', 'Pawan Kalyan', 'Chiranjeevi', 'Nani', 'Samantha',
		'Kajal Aggarwal', 'Anushka Shetty', 'Nayanthara', 'Vijay Sethupathi', 'Kamal Haasan',
	),

	'movies' => array(
		array( 'RRR', 2022, 187, array( 'Action', 'Drama', 'Adventure' ), 'Telugu', 'India', 'PG-13', '4K',
			'A fictional account of two legendary revolutionaries and their journey away from home before they began fighting for their country in the 1920s.',
			array( 3, 4, 7 ), 10, 8.0, array( 'featured', 'trending', 'popular' ), $b . 'BigBuckBunny.mp4',
			'/wE0I6efAW4cDDmZQWtwZMOW44EJ.jpg', '/wE0I6efAW4cDDmZQWtwZMOW44EJ.jpg' ),

		array( 'Baahubali 2: The Conclusion', 2017, 167, array( 'Action', 'Drama', 'Fantasy' ), 'Telugu', 'India', 'PG-13', '4K',
			'When Shiva, the son of Bahubali, learns about his heritage, he begins to look for answers. His story is juxtaposed with past events that unfolded in the Mahishmati Kingdom.',
			array( 0, 26, 25 ), 10, 8.2, array( 'featured', 'popular', 'trending' ), $b . 'ElephantsDream.mp4',
			'/21sC2assImQIYCEDA84Qh9d1RsK.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Baahubali: The Beginning', 2015, 159, array( 'Action', 'Drama', 'Fantasy' ), 'Telugu', 'India', 'PG-13', 'Full HD',
			'In ancient India, an adventurous and daring man becomes involved in a decades-old rivalry between two royal brothers.',
			array( 0, 26, 25 ), 10, 8.0, array( 'popular', 'featured' ), $b . 'Sintel.mp4',
			'/21sC2assImQIYCEDA84Qh9d1RsK.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Pushpa: The Rise', 2021, 179, array( 'Action', 'Crime', 'Drama' ), 'Telugu', 'India', 'R', '4K',
			'A labourer rises through the ranks of a red sandalwood smuggling syndicate, making powerful enemies along the way.',
			array( 1, 9 ), 12, 7.6, array( 'featured', 'trending', 'popular' ), $b . 'TearsOfSteel.mp4',
			'/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Pushpa 2: The Rule', 2024, 200, array( 'Action', 'Crime', 'Drama' ), 'Telugu', 'India', 'R', '4K',
			'Pushpa Raj returns, now a feared smuggler, as rivalries and politics threaten everything he has built.',
			array( 1, 9 ), 12, 7.8, array( 'featured', 'new_release', 'trending' ), $b . 'BigBuckBunny.mp4',
			'/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'K.G.F: Chapter 2', 2022, 168, array( 'Action', 'Crime', 'Drama' ), 'Kannada', 'India', 'R', '4K',
			'Rocky continues his quest for supremacy in the Kolar Gold Fields while facing new enemies and the government.',
			array( 2 ), 13, 8.3, array( 'featured', 'trending', 'popular' ), $b . 'ElephantsDream.mp4',
			'/khNVygolU0TxLIDWff5tQlAhZ23.jpg', '/gG9fTyDL03fiKnOpf2tr01sncnt.jpg' ),

		array( 'K.G.F: Chapter 1', 2018, 156, array( 'Action', 'Crime', 'Drama' ), 'Kannada', 'India', 'R', 'Full HD',
			'A young man rises from poverty to become a powerful figure in the illegal gold mines of Kolar.',
			array( 2 ), 13, 8.2, array( 'popular', 'trending' ), $b . 'Sintel.mp4',
			'/khNVygolU0TxLIDWff5tQlAhZ23.jpg', '/gG9fTyDL03fiKnOpf2tr01sncnt.jpg' ),

		array( 'Kalki 2898 AD', 2024, 181, array( 'Sci-Fi', 'Action', 'Adventure' ), 'Telugu', 'India', 'PG-13', '4K',
			'A modern-day avatar of Vishnu, a Hindu god, is believed to have descended to earth to protect the world from evil forces.',
			array( 0, 8, 6 ), 11, 7.0, array( 'featured', 'new_release', 'trending' ), $b . 'TearsOfSteel.mp4',
			'/rstcAnBeCkxNQjNp3YXrF6IP1tW.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Salaar: Part 1 – Ceasefire', 2023, 175, array( 'Action', 'Crime', 'Thriller' ), 'Telugu', 'India', 'R', '4K',
			'The fate of a violently contested city hangs on the tense, blood-soaked bond between two childhood friends.',
			array( 0 ), 13, 6.6, array( 'featured', 'trending', 'new_release' ), $b . 'BigBuckBunny.mp4',
			'/nlu9WbcetNFRGXXPWITr30ob7W6.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Magadheera', 2009, 166, array( 'Action', 'Fantasy', 'Romance' ), 'Telugu', 'India', 'PG-13', 'Full HD',
			'A motorcycle stuntman discovers he is the reincarnation of a warrior from a past life and must reclaim his destiny.',
			array( 3, 26 ), 10, 7.7, array( 'popular', 'featured' ), $b . 'ElephantsDream.mp4',
			'/xK7MEV56GF291VG0U5XnVJuvNv3.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Ala Vaikunthapurramuloo', 2020, 165, array( 'Action', 'Comedy', 'Drama' ), 'Telugu', 'India', 'PG-13', 'Full HD',
			'Bantu grows up in a middle-class home while the wealthy family he was switched with at birth lives in luxury. Fate brings them together.',
			array( 1, 24 ), 12, 7.4, array( 'popular', 'trending' ), $b . 'Sintel.mp4',
			'/2rzORJaegE2bbKNVkQXbZCeV0BP.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Rangasthalam', 2018, 179, array( 'Action', 'Drama' ), 'Telugu', 'India', 'PG-13', 'Full HD',
			'The fear of his elder brother\'s death starts to haunt a hearing-impaired man after the former decides to contest against the village president.',
			array( 3, 9 ), 12, 8.3, array( 'popular', 'featured' ), $b . 'TearsOfSteel.mp4',
			'/yiEzDgBBFC25Zd6z0r7sMngn5vr.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Arjun Reddy', 2017, 182, array( 'Drama', 'Romance' ), 'Telugu', 'India', 'R', 'Full HD',
			'A short-tempered house surgeon gets used to drugs and drinks when his girlfriend is forced to marry another person.',
			array( 18 ), 16, 8.1, array( 'popular', 'trending' ), $b . 'BigBuckBunny.mp4',
			'/kHubDgL59I5hCn7ccBYvU7bKY1r.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Sita Ramam', 2022, 163, array( 'Drama', 'Romance', 'War' ), 'Telugu', 'India', 'PG-13', '4K',
			'An orphan soldier, Lieutenant Ram, gets letters from a lady named Sita. He finds out that she is waiting for her lover to return from the war.',
			array( 23, 24 ), 17, 8.1, array( 'featured', 'popular', 'new_release' ), $b . 'ElephantsDream.mp4',
			'/t1O94ZBzsQXJihtVkrsStRLyUDR.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Hanuman', 2024, 158, array( 'Action', 'Fantasy', 'Adventure' ), 'Telugu', 'India', 'PG-13', '4K',
			'An imaginary place called Anjanadri, set in the modern era, where Hanumanth\'s life changes after he gets divine powers.',
			array( 23 ), 11, 7.6, array( 'new_release', 'trending', 'popular' ), $b . 'Sintel.mp4',
			'/rstcAnBeCkxNQjNp3YXrF6IP1tW.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Guntur Kaaram', 2024, 159, array( 'Action', 'Drama' ), 'Telugu', 'India', 'PG-13', '4K',
			'Years after his mother abandons him, a man confronts family secrets and a web of power in his hometown of Guntur.',
			array( 20 ), 12, 5.4, array( 'new_release' ), $b . 'TearsOfSteel.mp4',
			'/2rzORJaegE2bbKNVkQXbZCeV0BP.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Dangal', 2016, 161, array( 'Drama', 'Sport', 'Biography' ), 'Hindi', 'India', 'PG', 'Full HD',
			'Former wrestler Mahavir Singh Phogat and his two wrestler daughters struggle towards glory at the Commonwealth Games.',
			array( 5 ), 14, 8.3, array( 'featured', 'popular', 'trending' ), $b . 'BigBuckBunny.mp4',
			'/cJRPOLEexI7qp2DKtFfCh7YaaUG.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( '3 Idiots', 2009, 170, array( 'Comedy', 'Drama' ), 'Hindi', 'India', 'PG-13', 'Full HD',
			'Two friends are searching for their long lost companion. They revisit their college days and recall the memories of their friend who inspired them to think differently.',
			array( 5 ), 15, 8.4, array( 'featured', 'popular' ), $b . 'ElephantsDream.mp4',
			'/66A9MqXOyVFCssoloscw79z8Tew.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Jawan', 2023, 169, array( 'Action', 'Thriller', 'Drama' ), 'Hindi', 'India', 'PG-13', '4K',
			'A man is driven by a personal vendetta to rectify the wrongs in society, while keeping a promise made years ago.',
			array( 6, 8 ), 11, 7.0, array( 'featured', 'trending', 'new_release' ), $b . 'Sintel.mp4',
			'/jFt1gS4BGHlK8xt76Y81Alp4dbt.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Pathaan', 2023, 146, array( 'Action', 'Thriller' ), 'Hindi', 'India', 'PG-13', '4K',
			'An Indian spy takes on the leader of a group of mercenaries who have nefarious plans to target his homeland.',
			array( 6 ), 11, 6.0, array( 'trending', 'popular', 'new_release' ), $b . 'TearsOfSteel.mp4',
			'/jFt1gS4BGHlK8xt76Y81Alp4dbt.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Animal', 2023, 201, array( 'Action', 'Crime', 'Drama' ), 'Hindi', 'India', 'R', '4K',
			'A son\'s love for his father turns obsessive and violent when the latter is attacked by unknown forces.',
			array( 6 ), 16, 6.2, array( 'trending', 'new_release' ), $b . 'BigBuckBunny.mp4',
			'/iHPF4rt8HTuDZzNH1L2FCiPzN48.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Kabir Singh', 2019, 172, array( 'Drama', 'Romance' ), 'Hindi', 'India', 'R', 'Full HD',
			'A short-tempered house surgeon gets used to drugs and drinks when his girlfriend is forced to marry someone else.',
			array( 19 ), 16, 7.0, array( 'popular' ), $b . 'ElephantsDream.mp4',
			'/iHPF4rt8HTuDZzNH1L2FCiPzN48.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'PK', 2014, 153, array( 'Comedy', 'Drama', 'Sci-Fi' ), 'Hindi', 'India', 'PG-13', 'Full HD',
			'An alien on Earth loses the only device that can help him contact his planet. His innocent nature and child-like questions force people to re-evaluate their religious beliefs.',
			array( 5 ), 15, 8.1, array( 'popular', 'featured' ), $b . 'Sintel.mp4',
			'/66A9MqXOyVFCssoloscw79z8Tew.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Lagaan', 2001, 224, array( 'Drama', 'Sport', 'History' ), 'Hindi', 'India', 'PG', 'HD',
			'The people of a small village in Victorian India stake their future on a game of cricket against their ruthless British rulers.',
			array( 5 ), 14, 8.1, array( 'popular' ), $b . 'TearsOfSteel.mp4',
			'/cJRPOLEexI7qp2DKtFfCh7YaaUG.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Andhadhun', 2018, 139, array( 'Crime', 'Thriller', 'Comedy' ), 'Hindi', 'India', 'R', 'Full HD',
			'A series of mysterious events change the life of a blind pianist who now must report a crime that was actually never witnessed by him.',
			array( 19 ), 15, 8.2, array( 'popular', 'trending' ), $b . 'BigBuckBunny.mp4',
			'/iHPF4rt8HTuDZzNH1L2FCiPzN48.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Leo', 2023, 164, array( 'Action', 'Thriller', 'Crime' ), 'Tamil', 'India', 'R', '4K',
			'Parthiban is a mild-mannered cafe owner in Kashmir whose quiet life is threatened when his past as a ruthless gangster is revealed.',
			array( 28 ), 13, 7.2, array( 'trending', 'new_release', 'popular' ), $b . 'ElephantsDream.mp4',
			'/nlu9WbcetNFRGXXPWITr30ob7W6.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Jailer', 2023, 168, array( 'Action', 'Comedy', 'Crime' ), 'Tamil', 'India', 'PG-13', '4K',
			'A retired jailer goes on a manhunt to find his son\'s killers. But the path he takes uncovers a larger conspiracy.',
			array( 27 ), 13, 7.1, array( 'trending', 'popular', 'new_release' ), $b . 'Sintel.mp4',
			'/xK7MEV56GF291VG0U5XnVJuvNv3.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Vikram', 2022, 174, array( 'Action', 'Thriller', 'Crime' ), 'Tamil', 'India', 'R', '4K',
			'A special agent investigates a series of murders committed by a masked group of vigilantes.',
			array( 29 ), 13, 8.3, array( 'featured', 'trending', 'popular' ), $b . 'TearsOfSteel.mp4',
			'/khNVygolU0TxLIDWff5tQlAhZ23.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Master', 2021, 179, array( 'Action', 'Thriller', 'Drama' ), 'Tamil', 'India', 'R', 'Full HD',
			'An alcoholic professor is sent to a juvenile school, where he clashes with a gangster who uses the students for crime.',
			array( 28 ), 13, 7.3, array( 'popular', 'trending' ), $b . 'BigBuckBunny.mp4',
			'/nlu9WbcetNFRGXXPWITr30ob7W6.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Kantara', 2022, 148, array( 'Action', 'Drama', 'Thriller' ), 'Kannada', 'India', 'PG-13', '4K',
			'A conflict erupts between the people of a village in the forest and the officers of the forest department.',
			array( 2 ), 13, 8.2, array( 'featured', 'popular', 'trending' ), $b . 'ElephantsDream.mp4',
			'/yiEzDgBBFC25Zd6z0r7sMngn5vr.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Soorarai Pottru', 2020, 153, array( 'Drama', 'Biography' ), 'Tamil', 'India', 'PG-13', 'Full HD',
			'Nedumaaran Rajangam sets out to make the common man fly and gives rise to India\'s first low-cost airline.',
			array( 28 ), 14, 8.7, array( 'popular', 'featured' ), $b . 'Sintel.mp4',
			'/t1O94ZBzsQXJihtVkrsStRLyUDR.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Asuran', 2019, 141, array( 'Action', 'Drama' ), 'Tamil', 'India', 'R', 'Full HD',
			'The son of a farmer from an upper-caste family dies after a violent incident, and a farmer from a lower-caste family is forced to go on the run.',
			array( 28 ), 14, 8.5, array( 'popular' ), $b . 'TearsOfSteel.mp4',
			'/yiEzDgBBFC25Zd6z0r7sMngn5vr.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg' ),

		array( 'Jai Bhim', 2021, 164, array( 'Drama', 'Crime' ), 'Tamil', 'India', 'PG-13', 'Full HD',
			'A tribal woman seeks justice when her husband is arrested and goes missing under police custody.',
			array( 28 ), 14, 8.8, array( 'featured', 'popular' ), $b . 'BigBuckBunny.mp4',
			'/t1O94ZBzsQXJihtVkrsStRLyUDR.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg' ),

		array( 'Paper Kingdoms', 2025, 96, array( 'Animation', 'Family', 'Kids' ), 'English', 'Japan', 'G', 'HD',
			'Two origami siblings race to save their folded world before the rain arrives.',
			array( 24, 9 ), 11, 7.9, array( 'new_release', 'popular' ), $b . 'TearsOfSteel.mp4' ),

		array( 'Orbit Academy', 2025, 105, array( 'Sci-Fi', 'Kids', 'Adventure' ), 'English', 'United States', 'TV-Y7', '4K',
			'Young cadets train on a space station that orbits a mysterious gas giant.',
			array( 24, 9 ), 11, 7.6, array( 'featured', 'new_release' ), $b . 'BigBuckBunny.mp4' ),

		array( 'Midnight Orbit', 2025, 128, array( 'Sci-Fi', 'Thriller' ), 'English', 'United States', 'PG-13', 'Full HD',
			'A salvage pilot finds a silent station circling a dead moon and a message addressed to her.',
			array( 6, 8 ), 11, 8.1, array( 'featured', 'trending', 'new_release' ), $b . 'ElephantsDream.mp4' ),

		array( 'Neon Harbor', 2023, 104, array( 'Action', 'Crime' ), 'English', 'United States', 'R', 'Full HD',
			'A retired courier is dragged back into a city of rain, neon and unpaid debts.',
			array( 6 ), 13, 7.2, array( 'trending', 'popular' ), $b . 'Sintel.mp4' ),

		array( 'The Last Lantern', 2024, 112, array( 'Fantasy', 'Adventure' ), 'English', 'United Kingdom', 'PG', '4K',
			'The final lamplighter of a drowned city must carry the last flame across a sea of glass.',
			array( 8, 7 ), 10, 7.8, array( 'featured', 'popular' ), $b . 'TearsOfSteel.mp4' ),
	),

	'series' => array(
		array(
			'Sacrifice', 2024, array( 'Drama', 'Crime', 'Thriller' ), 'Hindi', 'India', 'TV-MA', '4K',
			'A cop and a gangster\'s lives intertwine across decades of crime and betrayal in Mumbai.',
			array(
				array( 1, 1, 'Blood Debt', $b . 'BigBuckBunny.mp4' ),
				array( 1, 2, 'The Deal', $b . 'ElephantsDream.mp4' ),
				array( 1, 3, 'Witness', $b . 'Sintel.mp4' ),
				array( 1, 4, 'Aftermath', $b . 'TearsOfSteel.mp4' ),
				array( 1, 5, 'Crossfire', $b . 'BigBuckBunny.mp4' ),
			),
			array( 6, 19 ), 16, 7.8, array( 'featured', 'trending', 'new_release' ),
			'/iHPF4rt8HTuDZzNH1L2FCiPzN48.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg',
		),
		array(
			'Farzi', 2023, array( 'Crime', 'Drama', 'Thriller' ), 'Hindi', 'India', 'TV-MA', '4K',
			'An artist-turned-counterfeiter and a determined officer clash in a high-stakes battle over fake currency.',
			array(
				array( 1, 1, 'The Artist', $b . 'ElephantsDream.mp4' ),
				array( 1, 2, 'Ink', $b . 'Sintel.mp4' ),
				array( 1, 3, 'Network', $b . 'TearsOfSteel.mp4' ),
				array( 1, 4, 'Trap', $b . 'BigBuckBunny.mp4' ),
			),
			array( 19 ), 16, 8.2, array( 'featured', 'popular', 'trending' ),
			'/jFt1gS4BGHlK8xt76Y81Alp4dbt.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg',
		),
		array(
			'The Family Man', 2021, array( 'Action', 'Drama', 'Thriller' ), 'Hindi', 'India', 'TV-MA', 'Full HD',
			'A middle-class man moonlights as an intelligence officer while balancing family life and national security threats.',
			array(
				array( 1, 1, 'The Briefing', $b . 'Sintel.mp4' ),
				array( 1, 2, 'Home Front', $b . 'TearsOfSteel.mp4' ),
				array( 1, 3, 'Cross Border', $b . 'BigBuckBunny.mp4' ),
				array( 1, 4, 'Countdown', $b . 'ElephantsDream.mp4' ),
				array( 2, 1, 'New Threat', $b . 'Sintel.mp4' ),
				array( 2, 2, 'Shadows', $b . 'TearsOfSteel.mp4' ),
			),
			array( 6 ), 14, 8.5, array( 'featured', 'popular', 'trending' ),
			'/cJRPOLEexI7qp2DKtFfCh7YaaUG.jpg', '/h6Pd89ngvl9quPVsx3KoJlQsvk9.jpg',
		),
		array(
			'Scam 1992', 2020, array( 'Drama', 'Crime', 'Biography' ), 'Hindi', 'India', 'TV-14', 'Full HD',
			'The rise and fall of Harshad Mehta, whose bull run on the Bombay Stock Exchange made him a folk hero — until the crash.',
			array(
				array( 1, 1, 'The Big Bull', $b . 'BigBuckBunny.mp4' ),
				array( 1, 2, 'Ready Forward', $b . 'ElephantsDream.mp4' ),
				array( 1, 3, 'The Game', $b . 'Sintel.mp4' ),
				array( 1, 4, 'Exposure', $b . 'TearsOfSteel.mp4' ),
				array( 1, 5, 'Fall', $b . 'BigBuckBunny.mp4' ),
			),
			array( 19 ), 15, 9.2, array( 'featured', 'popular' ),
			'/66A9MqXOyVFCssoloscw79z8Tew.jpg', '/21sC2assImQIYCEDA84Qh9d1RsK.jpg',
		),
		array(
			'Harbor Lights', 2025, array( 'Drama', 'Mystery' ), 'English', 'United States', 'TV-14', 'HD',
			'A small lighthouse town hides one secret per family. Season one follows the new keeper.',
			array(
				array( 1, 1, 'The New Keeper', $b . 'BigBuckBunny.mp4' ),
				array( 1, 2, 'Fog Signals', $b . 'ElephantsDream.mp4' ),
				array( 1, 3, 'The Locked Room', $b . 'Sintel.mp4' ),
				array( 1, 4, 'High Tide', $b . 'TearsOfSteel.mp4' ),
			),
			array( 6, 8 ), 11, 7.7, array( 'featured', 'popular', 'trending' ),
		),
		array(
			'Neon District', 2024, array( 'Crime', 'Thriller', 'Action' ), 'English', 'United States', 'TV-MA', '4K',
			'Detectives in a rain-soaked megacity chase a syndicate that trades in stolen identities.',
			array(
				array( 1, 1, 'First Light', $b . 'BigBuckBunny.mp4' ),
				array( 1, 2, 'Glass Alleys', $b . 'ElephantsDream.mp4' ),
				array( 1, 3, 'The Courier', $b . 'Sintel.mp4' ),
				array( 1, 4, 'Blackout', $b . 'TearsOfSteel.mp4' ),
				array( 1, 5, 'Mirror Run', $b . 'BigBuckBunny.mp4' ),
			),
			array( 6, 19 ), 13, 8.1, array( 'trending', 'popular', 'new_release' ),
		),
		array(
			'Sakura High', 2025, array( 'Drama', 'Romance', 'Kids' ), 'Japanese', 'Japan', 'TV-14', 'Full HD',
			'A transfer student joins a high-school club that only meets under the cherry blossoms.',
			array(
				array( 1, 1, 'First Petal', $b . 'ElephantsDream.mp4' ),
				array( 1, 2, 'Club Rules', $b . 'Sintel.mp4' ),
				array( 1, 3, 'Rain Day', $b . 'TearsOfSteel.mp4' ),
			),
			array( 9, 24 ), 11, 7.5, array( 'new_release', 'popular' ),
		),
		array(
			'Orbit Academy', 2025, array( 'Sci-Fi', 'Kids', 'Adventure' ), 'English', 'United States', 'TV-Y7', '4K',
			'Young cadets train on a space station that orbits a mysterious gas giant.',
			array(
				array( 1, 1, 'Launch Day', $b . 'TearsOfSteel.mp4' ),
				array( 1, 2, 'Gravity Well', $b . 'BigBuckBunny.mp4' ),
				array( 1, 3, 'Silent Zone', $b . 'ElephantsDream.mp4' ),
				array( 1, 4, 'Home Signal', $b . 'Sintel.mp4' ),
			),
			array( 24, 9 ), 11, 7.6, array( 'featured', 'new_release' ),
		),
	),

	'channels' => array(
		array( 'MovieFlix News 24', 'News', '101', 'Round-the-clock news desk covering entertainment and the MovieFlix universe.', $hls, 'news24' ),
		array( 'Action Arena', 'Movies', '201', 'Non-stop action movie blocks — Indian and global.', $b . 'BigBuckBunny.mp4', 'action-arena' ),
		array( 'Kids Zone Live', 'Kids', '301', 'Animated shorts, story time and interactive segments for younger viewers.', $b . 'ElephantsDream.mp4', 'kids-zone' ),
		array( 'Sports Pulse', 'Sports', '401', 'Live sports highlights, analysis and match coverage.', $b . 'Sintel.mp4', 'sports-pulse' ),
		array( 'Tollywood Live', 'Movies', '202', 'Telugu blockbusters and behind-the-scenes specials in continuous rotation.', $b . 'TearsOfSteel.mp4', 'tollywood' ),
		array( 'Bollywood Beats', 'Movies', '203', 'Hindi cinema classics and new releases streaming 24/7.', $b . 'BigBuckBunny.mp4', 'bollywood' ),
		array( 'Comedy Central Demo', 'Comedy', '501', 'Stand-up specials and sketch comedy from the MovieFlix vault.', $b . 'ElephantsDream.mp4', 'comedy-demo' ),
		array( 'Nature Watch', 'Documentary', '601', 'Wildlife, oceans and conservation documentaries live.', $b . 'Sintel.mp4', 'nature-watch' ),
		array( 'MusicBox Live', 'Music', '701', 'Concerts, sessions and music documentaries streaming 24/7.', $b . 'TearsOfSteel.mp4', 'musicbox' ),
		array( 'Sci-Fi Channel X', 'Sci-Fi', '801', 'Classic and new science-fiction features in themed blocks.', $hls, 'scifi-x' ),
		array( 'World Cinema', 'International', '901', 'Films from around the globe with rotating language tracks.', $b . 'BigBuckBunny.mp4', 'world-cinema' ),
		array( 'Kollywood Live', 'Movies', '204', 'Tamil cinema hits and festival favourites.', $b . 'ElephantsDream.mp4', 'kollywood' ),
	),
);
