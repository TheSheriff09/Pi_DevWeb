-- ============================================================
--  FORUM SEED DATA — Pi DevWeb Platform
--  Roles used:
--    ENTREPRENEURS : 1 (ali), 36 (abood), 38 (sarah), 43 (mohammed),
--                   120 (Oliver Young), 121 (Emma King), 122 (Liam Wright)
--    MENTORS       : 5 (mentor), 92 (linafadhel), 100 (Dr. Emily Watson),
--                   101 (Prof. James Carter), 102 (Ms. Sophia Liu)
--    EVALUATORS    : 40 (mohammed fadhel)
-- ============================================================

-- ────────────────────────────────────────────────────────────
--  FORUM POSTS  (15 realistic posts)
-- ────────────────────────────────────────────────────────────
INSERT INTO `forum_posts`
  (`title`, `content`, `created_at`, `updated_at`, `image_url`, `user_id`, `author_name`)
VALUES

-- POST 1 – Entrepreneur asking for pitch deck advice
(
  'How do I structure a winning pitch deck for seed funding?',
  'Hi everyone! I am preparing my first pitch deck to present to seed investors next month. My startup focuses on AI-driven logistics optimization for SMEs. I have a prototype and some early traction (3 pilot clients), but I am not sure how to present the financials and market size convincingly. Any advice on structure, must-have slides, and common mistakes to avoid would be greatly appreciated!',
  '2026-03-10 09:15:00', '2026-03-10 09:15:00', 'default_post.png', 1, 'ali'
),

-- POST 2 – Mentor sharing expertise
(
  'The 5 biggest mistakes entrepreneurs make when approaching mentors',
  'After years of mentoring early-stage startups, I have identified five recurring mistakes that kill the mentor-mentee relationship before it starts. 1) No clear ask — tell me exactly what you need. 2) Ghosting after the first session. 3) Expecting us to solve everything for free. 4) Not doing your homework. 5) Ignoring feedback. Happy to elaborate on each point — let''s discuss!',
  '2026-03-11 10:30:00', '2026-03-11 10:30:00', 'default_post.png', 100, 'Dr. Emily Watson'
),

-- POST 3 – Evaluator perspective on funding applications
(
  'What evaluators really look for in a funding application',
  'As an evaluator who reviews dozens of funding applications each month, I want to share what actually moves the needle. It is not just about having a great idea — we look at the team composition, the problem-solution fit, defensibility of the business model, and realistic financial projections. Founders who present honest risk assessments actually score higher. Stop hiding your weaknesses; show us you understand them.',
  '2026-03-12 08:45:00', '2026-03-12 08:45:00', 'default_post.png', 40, 'mohammed fadhel'
),

-- POST 4 – Entrepreneur asking about MVP validation
(
  'Best methods to validate your MVP without spending a lot of money',
  'We just finished building our MVP — a SaaS tool for restaurant inventory management. Before we invest more in development, I want to validate it with real users. What are the most cost-effective ways to get honest feedback? We have considered landing pages, fake-door tests, and cold outreach to restaurant owners. Has anyone had success with a specific approach? Our budget for validation is under $500.',
  '2026-03-13 14:00:00', '2026-03-13 14:00:00', 'default_post.png', 38, 'sarah'
),

-- POST 5 – Mentor post on networking
(
  'Why your network is your most underrated startup asset',
  'Every entrepreneur obsesses over product and funding, but your network is what actually opens doors. I have seen mediocre products thrive because the founder knew the right people, and brilliant products fail in silence. Here are my top 3 strategies even introverts can use: attend niche industry events, give value before asking, and follow up within 24 hours. What has worked for you?',
  '2026-03-14 11:20:00', '2026-03-14 11:20:00', 'default_post.png', 101, 'Prof. James Carter'
),

-- POST 6 – Entrepreneur sharing milestone
(
  'We just closed our first paying customer after 6 months of bootstrapping!',
  'I know this might seem small, but for our team of 3, landing the first paying client (a mid-size logistics company) after 6 months of survival mode feels like Mount Everest. Our product helps track driver fatigue using computer vision. We are not profitable yet, but this validates everything. To anyone grinding in silence right now — keep going. The first yes will come.',
  '2026-03-15 16:45:00', '2026-03-15 16:45:00', 'default_post.png', 120, 'Oliver Young'
),

-- POST 7 – Mentor on startup equity mistakes
(
  'Equity splits that destroy startups — learn from others'' pain',
  'One of the most common startup killers I see as a mentor is a bad equity split decided on day one when nobody has done any real work yet. Equal splits between founders sound fair but often lead to deadlock. Vesting schedules are non-negotiable. And giving away too much equity early to advisors is a trap. Let''s talk about what a healthy cap table looks like at pre-seed stage.',
  '2026-03-16 09:00:00', '2026-03-16 09:00:00', 'default_post.png', 92, 'linafadhel'
),

-- POST 8 – Entrepreneur question on B2B sales
(
  'Cold outreach for B2B SaaS — what actually works in 2026?',
  'We are building a B2B SaaS product for HR teams and we are struggling with cold outreach. Our email open rates are around 18% and reply rates are below 3%. We are targeting HR directors at companies with 200-500 employees. Has anyone cracked the code on personalised cold outreach at scale? Should we be using LinkedIn, email, or something else entirely? What tools do you recommend?',
  '2026-03-17 13:30:00', '2026-03-17 13:30:00', 'default_post.png', 121, 'Emma King'
),

-- POST 9 – Evaluator post on common red flags
(
  'Red flags in startup pitches that instantly lose evaluator trust',
  'After reviewing over 200 startup applications this year alone, here are the red flags that make evaluators like me put the application on the reject pile immediately: 1) Claiming "no competition" — every market has competition. 2) Projecting 10x revenue growth with no explanation. 3) A team with no domain expertise. 4) Vague problem statements. 5) No mention of customer discovery. If your pitch has any of these, fix them before submitting.',
  '2026-03-18 10:15:00', '2026-03-18 10:15:00', 'default_post.png', 40, 'mohammed fadhel'
),

-- POST 10 – Entrepreneur on mental health
(
  'The mental health side of entrepreneurship nobody talks about',
  'Three months ago I was ready to shut everything down. Burnt out, no revenue, co-founder tension, and family pressure. I want to open a real conversation about founder mental health because our community tends to glorify the hustle and hide the pain. What coping mechanisms have worked for you? I started therapy and weekly co-founder check-ins and it helped enormously. You are not alone.',
  '2026-03-19 17:00:00', '2026-03-19 17:00:00', 'default_post.png', 122, 'Liam Wright'
),

-- POST 11 – Mentor on product-market fit
(
  'How to know when you have actually found product-market fit',
  'Every founder claims to have product-market fit. Almost none of them do at early stage. Real PMF is when customers are genuinely upset at the thought of losing your product, when word-of-mouth drives a meaningful chunk of your growth, and when your churn is low without heavy intervention. Here is a simple test I use with my mentees: ask 10 customers how disappointed they would be if your product disappeared tomorrow. If 40%+ say "very disappointed", you are on to something.',
  '2026-03-20 08:30:00', '2026-03-20 08:30:00', 'default_post.png', 102, 'Ms. Sophia Liu'
),

-- POST 12 – Entrepreneur on legal setup
(
  'Legal basics every first-time founder needs to know before launching',
  'I made the mistake of launching without proper legal setup and it cost me 3 months and a lot of money to fix later. Here is what I wish someone had told me: register your company before signing any contracts, have a co-founder agreement with vesting, use an NDA template for early conversations, and understand the difference between a SAFE and a convertible note. Happy to share templates if useful.',
  '2026-03-21 11:00:00', '2026-03-21 11:00:00', 'default_post.png', 43, 'mohammed'
),

-- POST 13 – Mentor on market sizing
(
  'TAM SAM SOM — stop inflating your market size numbers',
  'I review pitch decks every week and the market sizing slide is almost always wrong. Founders love to start with a $500B TAM to sound impressive. But investors and evaluators can see through it immediately. Your SOM — the market you can actually reach — is what matters. Walk me through how you calculated your numbers and I will show you where you are probably overestimating.',
  '2026-03-22 14:45:00', '2026-03-22 14:45:00', 'default_post.png', 5, 'mentor'
),

-- POST 14 – Entrepreneur on hiring first employee
(
  'How did you hire your first employee? Looking for advice',
  'We are at the point where we need our first full-time hire — a full-stack developer. We are a pre-revenue startup (but with runway for 8 months). Should we offer equity compensation, a below-market salary, or a mix? How do you convince talented engineers to join a risky early-stage startup? Any red flags to watch out for during the interview process for this kind of hire?',
  '2026-03-23 09:30:00', '2026-03-23 09:30:00', 'default_post.png', 36, 'abood'
),

-- POST 15 – Community discussion on startup ecosystem
(
  'Is the startup ecosystem in Tunisia ready for a new wave of tech entrepreneurs?',
  'I attended a startup event in Tunis last week and I was genuinely impressed by the quality of ideas coming from young founders. But there are still structural challenges — access to capital, limited exit history, and brain drain. As someone who has been part of this ecosystem for years, I believe the next 3 years will be decisive. What do you think needs to change to unlock the full potential of Tunisian startups?',
  '2026-03-24 15:20:00', '2026-03-24 15:20:00', 'default_post.png', 40, 'mohammed fadhel'
);


-- ────────────────────────────────────────────────────────────
--  COMMENTS  (realistic replies from different roles)
-- ────────────────────────────────────────────────────────────
INSERT INTO `comments`
  (`content`, `created_at`, `post_id`, `user_id`, `author_name`)
VALUES

-- Comments on POST 1 (pitch deck)
('Great question! The most important slides are: Problem, Solution, Market Size, Traction, Team, and Ask. Keep it to 12 slides max. Investors care most about traction and team.', '2026-03-10 10:00:00', 1, 100, 'Dr. Emily Watson'),
('Your traction with 3 pilot clients is actually a strong signal. Lead with that in slide 2 right after the problem. Numbers speak louder than words.', '2026-03-10 10:45:00', 1, 40, 'mohammed fadhel'),
('I used the Guy Kawasaki 10/20/30 rule for my first pitch and it worked well. 10 slides, 20 minutes, 30pt font minimum. Simple and effective.', '2026-03-10 11:30:00', 1, 38, 'sarah'),
('Do not underestimate the financials slide. Show 3-year projections with clear assumptions. If your numbers look too optimistic, investors will question your judgment.', '2026-03-10 13:00:00', 1, 101, 'Prof. James Carter'),

-- Comments on POST 2 (mentor mistakes)
('Point 3 really hit home. I had a mentor who spent 3 sessions with me for free and I kept coming back with vague questions. I only realized later how disrespectful that was.', '2026-03-11 11:00:00', 2, 1, 'ali'),
('The "no clear ask" mistake is so common. I always tell entrepreneurs — before meeting a mentor, write down exactly what decision you need help making.', '2026-03-11 12:00:00', 2, 92, 'linafadhel'),
('Ignoring feedback is the worst. I had a mentee who nodded at everything I said and then did the exact opposite. We parted ways after the third session.', '2026-03-11 14:00:00', 2, 102, 'Ms. Sophia Liu'),

-- Comments on POST 3 (evaluator perspective)
('This is exactly what I needed to hear before my next application. Honest about weaknesses — I will rewrite that section completely.', '2026-03-12 09:30:00', 3, 120, 'Oliver Young'),
('Could you share what you consider a realistic financial projection? Are 3-year projections standard or do you prefer 5-year?', '2026-03-12 10:00:00', 3, 38, 'sarah'),
('We lost a funding application last year and I am now convinced it was the financial section. Thank you for this clarity.', '2026-03-12 11:00:00', 3, 122, 'Liam Wright'),

-- Comments on POST 4 (MVP validation)
('Smoke tests and landing pages work great for early validation. Carrd.co lets you build a landing page in under an hour for free. Track email signups as demand signal.', '2026-03-13 14:30:00', 4, 101, 'Prof. James Carter'),
('For restaurant inventory, I would go direct. Walk into 10 restaurants, ask the manager if you can watch how they do inventory, and offer to show your tool in exchange. You will learn more in one day than months of surveys.', '2026-03-13 15:00:00', 4, 100, 'Dr. Emily Watson'),
('We validated our SaaS with a fake checkout page before we built anything. Had 47 people try to "buy" — that was enough signal to continue building.', '2026-03-13 16:00:00', 4, 121, 'Emma King'),

-- Comments on POST 5 (network)
('100% agree. My first investor came through a Twitter DM. I had been consistently sharing insights about my industry for 6 months before he reached out.', '2026-03-14 12:00:00', 5, 43, 'mohammed'),
('For introverts — online communities like this one are goldmines. You can add value through writing and get discovered without having to work a room.', '2026-03-14 13:00:00', 5, 36, 'abood'),

-- Comments on POST 6 (first customer)
('Congratulations! This is massive. The first paying customer is proof that someone cares enough to actually pay. Keep us updated on your journey!', '2026-03-15 17:00:00', 6, 100, 'Dr. Emily Watson'),
('Six months of bootstrapping with no external help is incredibly hard. This post made my day. You deserve every success.', '2026-03-15 17:30:00', 6, 38, 'sarah'),
('Computer vision for driver fatigue — have you considered reaching out to insurance companies? They might be very interested in your data.', '2026-03-15 18:00:00', 6, 40, 'mohammed fadhel'),

-- Comments on POST 7 (equity)
('Vesting schedules saved my startup. My co-founder left at month 4 and because we had a 4-year vest with 1-year cliff, the damage was manageable.', '2026-03-16 10:00:00', 7, 1, 'ali'),
('What percentage do you typically recommend for early advisors? I have seen anything from 0.1% to 1%.', '2026-03-16 10:30:00', 7, 121, 'Emma King'),
('For advisors, 0.25% over 2 years with a 6-month cliff is a common and fair structure. Anything above 0.5% needs very strong justification.', '2026-03-16 11:00:00', 7, 102, 'Ms. Sophia Liu'),

-- Comments on POST 9 (red flags)
('The "no competition" claim is my biggest pet peeve. Every evaluator sees that as a sign the founder has not done their research.', '2026-03-18 11:00:00', 9, 101, 'Prof. James Carter'),
('I made mistake number 2 in my first pitch. Projected 15x revenue growth. The investor stopped me mid-slide to ask how. I had no good answer.', '2026-03-18 11:30:00', 9, 43, 'mohammed'),

-- Comments on POST 10 (mental health)
('Thank you for posting this. I had a panic attack during a board meeting last year. Nobody knew. The pressure founders carry in silence is real. More of this conversation please.', '2026-03-19 17:30:00', 10, 120, 'Oliver Young'),
('Therapy changed everything for me. Also, building a founder peer group outside my own company where I can be honest without judgment is invaluable.', '2026-03-19 18:00:00', 10, 38, 'sarah'),
('Co-founder weekly check-ins are underrated. We added a simple ritual: each person shares one win, one struggle, and one ask. 15 minutes every Monday. Game changer.', '2026-03-19 18:30:00', 10, 92, 'linafadhel'),

-- Comments on POST 11 (PMF)
('The 40% test you mentioned — is that similar to the Sean Ellis test? I have been wanting to run that on our user base for a while.', '2026-03-20 09:00:00', 11, 36, 'abood'),
('Yes, exactly the Sean Ellis framework. Run it via a quick email survey. It is simple and surprisingly revealing.', '2026-03-20 09:30:00', 11, 102, 'Ms. Sophia Liu'),

-- Comments on POST 13 (TAM SAM SOM)
('This is the slide I always struggle with. How do you recommend calculating SOM for a B2B SaaS in a niche market?', '2026-03-22 15:00:00', 13, 121, 'Emma King'),
('Start from win rate and average contract value. If you can realistically close 5% of your SAM in year 3, that is your SOM. Work backwards from there.', '2026-03-22 15:30:00', 13, 5, 'mentor'),

-- Comments on POST 14 (first hire)
('Mix of equity and below-market salary works best for mission-driven engineers. Make sure the equity vests over 4 years — it aligns incentives long term.', '2026-03-23 10:00:00', 14, 100, 'Dr. Emily Watson'),
('Red flag in interviews: candidates who only ask about salary and never about the product or the problem you are solving. You want people who care about the mission.', '2026-03-23 10:30:00', 14, 101, 'Prof. James Carter'),
('We hired our first developer through a referral from an accelerator peer. Do not underestimate the power of warm introductions.', '2026-03-23 11:00:00', 14, 122, 'Liam Wright'),

-- Comments on POST 15 (Tunisian ecosystem)
('The capital access problem is real, but it is improving. BFPME and several new VC funds are starting to look at pre-seed stage. The gap is still in Series A.', '2026-03-24 16:00:00', 15, 5, 'mentor'),
('Brain drain is the critical issue. We need to create enough early wins locally to show ambitious people they can build something meaningful here.', '2026-03-24 16:30:00', 15, 1, 'ali'),
('The talent is absolutely there. What is missing is a culture of risk-taking and more mentors who have actually built and exited companies locally.', '2026-03-24 17:00:00', 15, 92, 'linafadhel');


-- ────────────────────────────────────────────────────────────
--  INTERACTIONS (likes on posts from various users)
-- ────────────────────────────────────────────────────────────
INSERT INTO `interactions`
  (`post_id`, `user_id`, `type`, `created_at`)
VALUES
-- Post 1 likes
(1, 38, 'LIKE', '2026-03-10 12:00:00'),
(1, 40, 'LIKE', '2026-03-10 12:30:00'),
(1, 101, 'LIKE', '2026-03-10 13:00:00'),
(1, 120, 'LIKE', '2026-03-10 14:00:00'),
-- Post 2 likes
(2, 1, 'LIKE', '2026-03-11 11:30:00'),
(2, 36, 'LIKE', '2026-03-11 12:30:00'),
(2, 120, 'LOVE', '2026-03-11 13:00:00'),
-- Post 3 likes
(3, 120, 'LIKE', '2026-03-12 09:45:00'),
(3, 38, 'LIKE', '2026-03-12 10:15:00'),
(3, 1, 'LIKE', '2026-03-12 11:30:00'),
(3, 122, 'LOVE', '2026-03-12 12:00:00'),
-- Post 6 likes (celebration post — lots of love)
(6, 100, 'LOVE', '2026-03-15 17:15:00'),
(6, 38, 'LOVE', '2026-03-15 17:45:00'),
(6, 40, 'LIKE', '2026-03-15 18:15:00'),
(6, 101, 'LOVE', '2026-03-15 18:30:00'),
(6, 92, 'LOVE', '2026-03-15 19:00:00'),
(6, 1, 'LIKE', '2026-03-15 19:30:00'),
-- Post 10 likes (mental health — empathy reactions)
(10, 120, 'LOVE', '2026-03-19 17:45:00'),
(10, 38, 'LOVE', '2026-03-19 18:15:00'),
(10, 92, 'LOVE', '2026-03-19 18:45:00'),
(10, 1, 'LIKE', '2026-03-19 19:00:00'),
(10, 36, 'LIKE', '2026-03-19 19:15:00'),
-- Post 13 likes
(13, 121, 'LIKE', '2026-03-22 15:15:00'),
(13, 43, 'LIKE', '2026-03-22 16:00:00'),
(13, 38, 'LIKE', '2026-03-22 16:30:00'),
-- Post 15 likes
(15, 5, 'LIKE', '2026-03-24 16:15:00'),
(15, 1, 'LIKE', '2026-03-24 16:45:00'),
(15, 92, 'LOVE', '2026-03-24 17:15:00'),
(15, 122, 'LIKE', '2026-03-24 17:30:00');
