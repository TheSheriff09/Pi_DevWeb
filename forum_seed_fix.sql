-- ============================================================
--  CLEANUP orphaned comments & interactions (wrong post IDs)
-- ============================================================
DELETE FROM `comments`      WHERE `post_id` NOT IN (SELECT id FROM `forum_posts`);
DELETE FROM `interactions`  WHERE `post_id` NOT IN (SELECT id FROM `forum_posts`);

-- ============================================================
--  COMMENTS  (post IDs 30–44)
-- ============================================================
INSERT INTO `comments` (`content`, `created_at`, `post_id`, `user_id`, `author_name`) VALUES

-- POST 30: pitch deck
('Great question! The most important slides are: Problem, Solution, Market Size, Traction, Team, and Ask. Keep it to 12 slides max. Investors care most about traction and team.', '2026-03-10 10:00:00', 30, 100, 'Dr. Emily Watson'),
('Your traction with 3 pilot clients is actually a strong signal. Lead with that in slide 2 right after the problem. Numbers speak louder than words.', '2026-03-10 10:45:00', 30, 40, 'mohammed fadhel'),
('I used the Guy Kawasaki 10/20/30 rule for my first pitch and it worked well. 10 slides, 20 minutes, 30pt font minimum. Simple and effective.', '2026-03-10 11:30:00', 30, 38, 'sarah'),
('Do not underestimate the financials slide. Show 3-year projections with clear assumptions. If your numbers look too optimistic, investors will question your judgment.', '2026-03-10 13:00:00', 30, 101, 'Prof. James Carter'),

-- POST 31: mentor mistakes
('Point 3 really hit home. I had a mentor who spent 3 sessions with me for free and I kept coming back with vague questions. I only realized later how disrespectful that was.', '2026-03-11 11:00:00', 31, 1, 'ali'),
('The "no clear ask" mistake is so common. I always tell entrepreneurs — before meeting a mentor, write down exactly what decision you need help making.', '2026-03-11 12:00:00', 31, 92, 'linafadhel'),
('Ignoring feedback is the worst. I had a mentee who nodded at everything I said and then did the exact opposite. We parted ways after the third session.', '2026-03-11 14:00:00', 31, 102, 'Ms. Sophia Liu'),

-- POST 32: evaluator perspective
('This is exactly what I needed to hear before my next application. Honest about weaknesses — I will rewrite that section completely.', '2026-03-12 09:30:00', 32, 120, 'Oliver Young'),
('Could you share what you consider a realistic financial projection? Are 3-year projections standard or do you prefer 5-year?', '2026-03-12 10:00:00', 32, 38, 'sarah'),
('We lost a funding application last year and I am now convinced it was the financial section. Thank you for this clarity.', '2026-03-12 11:00:00', 32, 122, 'Liam Wright'),

-- POST 33: MVP validation
('Smoke tests and landing pages work great for early validation. Carrd.co lets you build a landing page in under an hour for free. Track email signups as demand signal.', '2026-03-13 14:30:00', 33, 101, 'Prof. James Carter'),
('For restaurant inventory, I would go direct. Walk into 10 restaurants, ask the manager if you can watch how they do inventory, and offer to show your tool in exchange.', '2026-03-13 15:00:00', 33, 100, 'Dr. Emily Watson'),
('We validated our SaaS with a fake checkout page before we built anything. Had 47 people try to sign up — that was enough signal to continue building.', '2026-03-13 16:00:00', 33, 121, 'Emma King'),

-- POST 34: networking
('My first investor came through a Twitter DM. I had been consistently sharing insights about my industry for 6 months before he reached out.', '2026-03-14 12:00:00', 34, 43, 'mohammed'),
('For introverts — online communities like this one are goldmines. You can add value through writing and get discovered without having to work a room.', '2026-03-14 13:00:00', 34, 36, 'abood'),

-- POST 35: first paying customer
('Congratulations! This is massive. The first paying customer is proof that someone cares enough to actually pay. Keep us updated on your journey!', '2026-03-15 17:00:00', 35, 100, 'Dr. Emily Watson'),
('Six months of bootstrapping with no external help is incredibly hard. This post made my day. You deserve every success.', '2026-03-15 17:30:00', 35, 38, 'sarah'),
('Computer vision for driver fatigue — have you considered reaching out to insurance companies? They might be very interested in your data.', '2026-03-15 18:00:00', 35, 40, 'mohammed fadhel'),

-- POST 36: equity splits
('Vesting schedules saved my startup. My co-founder left at month 4 and because we had a 4-year vest with 1-year cliff, the damage was manageable.', '2026-03-16 10:00:00', 36, 1, 'ali'),
('What percentage do you typically recommend for early advisors? I have seen anything from 0.1% to 1%.', '2026-03-16 10:30:00', 36, 121, 'Emma King'),
('For advisors, 0.25% over 2 years with a 6-month cliff is a common and fair structure. Anything above 0.5% needs very strong justification.', '2026-03-16 11:00:00', 36, 102, 'Ms. Sophia Liu'),

-- POST 38: red flags
('The "no competition" claim is my biggest pet peeve. Every evaluator sees that as a sign the founder has not done their research.', '2026-03-18 11:00:00', 38, 101, 'Prof. James Carter'),
('I made mistake number 2 in my first pitch. Projected 15x revenue growth. The investor stopped me mid-slide to ask how. I had no good answer.', '2026-03-18 11:30:00', 38, 43, 'mohammed'),

-- POST 39: mental health
('Thank you for posting this. I had a panic attack during a board meeting last year. Nobody knew. The pressure founders carry in silence is real. More of this conversation please.', '2026-03-19 17:30:00', 39, 120, 'Oliver Young'),
('Therapy changed everything for me. Also, building a founder peer group outside my own company where I can be honest without judgment is invaluable.', '2026-03-19 18:00:00', 39, 38, 'sarah'),
('Co-founder weekly check-ins are underrated. We added a simple ritual: each person shares one win, one struggle, and one ask. 15 minutes every Monday. Game changer.', '2026-03-19 18:30:00', 39, 92, 'linafadhel'),

-- POST 40: product-market fit
('The 40% test you mentioned — is that similar to the Sean Ellis test? I have been wanting to run that on our user base for a while.', '2026-03-20 09:00:00', 40, 36, 'abood'),
('Yes, exactly the Sean Ellis framework. Run it via a quick email survey. It is simple and surprisingly revealing.', '2026-03-20 09:30:00', 40, 102, 'Ms. Sophia Liu'),

-- POST 42: TAM SAM SOM
('This is the slide I always struggle with. How do you recommend calculating SOM for a B2B SaaS in a niche market?', '2026-03-22 15:00:00', 42, 121, 'Emma King'),
('Start from win rate and average contract value. If you can realistically close 5% of your SAM in year 3, that is your SOM. Work backwards from there.', '2026-03-22 15:30:00', 42, 5, 'mentor'),

-- POST 43: first hire
('Mix of equity and below-market salary works best for mission-driven engineers. Make sure the equity vests over 4 years — it aligns incentives long term.', '2026-03-23 10:00:00', 43, 100, 'Dr. Emily Watson'),
('Red flag in interviews: candidates who only ask about salary and never about the product or the problem you are solving. You want people who care about the mission.', '2026-03-23 10:30:00', 43, 101, 'Prof. James Carter'),
('We hired our first developer through a referral from an accelerator peer. Do not underestimate the power of warm introductions.', '2026-03-23 11:00:00', 43, 122, 'Liam Wright'),

-- POST 44: Tunisian ecosystem
('The capital access problem is real, but it is improving. BFPME and several new VC funds are starting to look at pre-seed stage. The gap is still in Series A.', '2026-03-24 16:00:00', 44, 5, 'mentor'),
('Brain drain is the critical issue. We need to create enough early wins locally to show ambitious people they can build something meaningful here.', '2026-03-24 16:30:00', 44, 1, 'ali'),
('The talent is absolutely there. What is missing is a culture of risk-taking and more mentors who have actually built and exited companies locally.', '2026-03-24 17:00:00', 44, 92, 'linafadhel');


-- ============================================================
--  INTERACTIONS  (post IDs 30–44)
-- ============================================================
INSERT INTO `interactions` (`post_id`, `user_id`, `type`, `created_at`) VALUES
-- Post 30
(30, 38, 'LIKE', '2026-03-10 12:00:00'),
(30, 40, 'LIKE', '2026-03-10 12:30:00'),
(30, 101, 'LIKE', '2026-03-10 13:00:00'),
(30, 120, 'LIKE', '2026-03-10 14:00:00'),
-- Post 31
(31, 1, 'LIKE', '2026-03-11 11:30:00'),
(31, 36, 'LIKE', '2026-03-11 12:30:00'),
(31, 120, 'LOVE', '2026-03-11 13:00:00'),
-- Post 32
(32, 120, 'LIKE', '2026-03-12 09:45:00'),
(32, 38, 'LIKE', '2026-03-12 10:15:00'),
(32, 1, 'LIKE', '2026-03-12 11:30:00'),
(32, 122, 'LOVE', '2026-03-12 12:00:00'),
-- Post 35 (celebration)
(35, 100, 'LOVE', '2026-03-15 17:15:00'),
(35, 38, 'LOVE', '2026-03-15 17:45:00'),
(35, 40, 'LIKE', '2026-03-15 18:15:00'),
(35, 101, 'LOVE', '2026-03-15 18:30:00'),
(35, 92, 'LOVE', '2026-03-15 19:00:00'),
(35, 1, 'LIKE', '2026-03-15 19:30:00'),
-- Post 39 (mental health)
(39, 120, 'LOVE', '2026-03-19 17:45:00'),
(39, 38, 'LOVE', '2026-03-19 18:15:00'),
(39, 92, 'LOVE', '2026-03-19 18:45:00'),
(39, 1, 'LIKE', '2026-03-19 19:00:00'),
(39, 36, 'LIKE', '2026-03-19 19:15:00'),
-- Post 42
(42, 121, 'LIKE', '2026-03-22 15:15:00'),
(42, 43, 'LIKE', '2026-03-22 16:00:00'),
(42, 38, 'LIKE', '2026-03-22 16:30:00'),
-- Post 44
(44, 5, 'LIKE', '2026-03-24 16:15:00'),
(44, 1, 'LIKE', '2026-03-24 16:45:00'),
(44, 92, 'LOVE', '2026-03-24 17:15:00'),
(44, 122, 'LIKE', '2026-03-24 17:30:00');
