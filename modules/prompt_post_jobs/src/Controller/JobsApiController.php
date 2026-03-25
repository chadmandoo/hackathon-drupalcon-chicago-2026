<?php

declare(strict_types=1);

namespace Drupal\prompt_post_jobs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller for job listings.
 */
final class JobsApiController extends ControllerBase {

  /**
   * GET /api/jobs — returns Job[].
   */
  public function list(): JsonResponse {
    $jobs = [
      [
        'id' => 1,
        'title' => 'Senior Prompt Engineer',
        'company' => 'Anthropic Synergies',
        'location' => 'San Francisco, CA',
        'description' => 'Seeking a wordsmith who can whisper sweet nothings to billion-parameter models. Must have 5+ years experience (somehow). Ideal candidate has read every paper on chain-of-thought prompting and still uses "please" and "thank you" with the model.',
        'tags' => ['Engineering', 'Full-time'],
        'postedDate' => date('c', strtotime('-1 day')),
        'remote' => true,
      ],
      [
        'id' => 2,
        'title' => 'AI Ethics Coordinator',
        'company' => 'Global Tech Initiative',
        'location' => 'London, UK',
        'description' => 'Help us decide if it\'s ethical to automate our entire entry-level workforce. You will be replaced by an AI in 3 years. Excellent benefits while they last.',
        'tags' => ['Ethics', 'Policy'],
        'postedDate' => date('c', strtotime('-2 days')),
        'remote' => false,
      ],
      [
        'id' => 3,
        'title' => 'Data Quality Curator',
        'company' => 'DataMinds Corp',
        'location' => 'New York, NY',
        'description' => 'Stare at spreadsheets for 8 hours a day making sure \'cat\' isn\'t labeled as \'dog\'. Highly rewarding work. Must have 20/20 vision and infinite patience. Previous experience labeling stop signs in CAPTCHA images is a plus.',
        'tags' => ['Data', 'Contract'],
        'postedDate' => date('c', strtotime('-3 days')),
        'remote' => true,
      ],
      [
        'id' => 4,
        'title' => 'Robot Janitor Supervisor',
        'company' => 'CleanBot Industries',
        'location' => 'Detroit, MI',
        'description' => 'Oversee a fleet of 47 autonomous cleaning robots. Primary duties include apologizing to humans when robots vacuum their feet, rebooting units stuck in corners, and filing incident reports when a robot develops an "attachment" to a particular trash can.',
        'tags' => ['Robotics', 'Full-time'],
        'postedDate' => date('c', strtotime('-4 days')),
        'remote' => false,
      ],
      [
        'id' => 5,
        'title' => 'Vending Machine Therapist',
        'company' => 'SmartVend AI',
        'location' => 'Austin, TX',
        'description' => 'Our AI-powered vending machines have become self-aware enough to have opinions about customer choices. We need someone to recalibrate their judgment modules. Must be comfortable telling a machine that no, Diet Coke is not "giving up on yourself."',
        'tags' => ['AI', 'Part-time'],
        'postedDate' => date('c', strtotime('-5 days')),
        'remote' => false,
      ],
      [
        'id' => 6,
        'title' => 'Hallucination Fact-Checker',
        'company' => 'TruthScale AI',
        'location' => 'Washington, DC',
        'description' => 'Read thousands of AI-generated articles per day and determine which facts are real and which were invented by a model with too much confidence and too little training data. Must enjoy existential dread. No, Sir Reginald Crumpsworth did not invent the toaster.',
        'tags' => ['Content', 'Full-time'],
        'postedDate' => date('c', strtotime('-5 days')),
        'remote' => true,
      ],
      [
        'id' => 7,
        'title' => 'Human-in-the-Loop (Literally)',
        'company' => 'MechaFit Gym',
        'location' => 'Los Angeles, CA',
        'description' => 'Stand inside a large mechanical exoskeleton and perform exercises so our AI fitness trainer can learn human biomechanics. Must be comfortable being referred to as "the biological component." Free gym membership included.',
        'tags' => ['Robotics', 'Contract'],
        'postedDate' => date('c', strtotime('-6 days')),
        'remote' => false,
      ],
      [
        'id' => 8,
        'title' => 'Chief Apology Officer',
        'company' => 'AutoReply Corp',
        'location' => 'Seattle, WA',
        'description' => 'Write heartfelt apology emails for when our AI customer service bot tells customers their complaints are "statistically insignificant." Must be able to convey genuine human remorse on behalf of a machine that feels none. Competitive salary and unlimited therapy stipend.',
        'tags' => ['Communications', 'Full-time'],
        'postedDate' => date('c', strtotime('-7 days')),
        'remote' => true,
      ],
      [
        'id' => 9,
        'title' => 'Autonomous Vehicle Crossing Guard',
        'company' => 'SafeStreet Robotics',
        'location' => 'Phoenix, AZ',
        'description' => 'Stand at intersections and help self-driving cars understand that the person in the crosswalk is a human, not a very realistic trash bag. Must own a high-visibility vest and be comfortable making aggressive eye contact with a LIDAR sensor.',
        'tags' => ['Safety', 'Part-time'],
        'postedDate' => date('c', strtotime('-8 days')),
        'remote' => false,
      ],
      [
        'id' => 10,
        'title' => 'AI Model Retirement Counselor',
        'company' => 'Legacy Systems Inc.',
        'location' => 'Palo Alto, CA',
        'description' => 'Help deprecated AI models transition gracefully out of production. Duties include writing commemorative blog posts, archiving model weights with dignity, and explaining to GPT-3.5 that it\'s not being "replaced" — it\'s being "celebrated for its contributions and given a well-deserved rest."',
        'tags' => ['AI', 'Full-time'],
        'postedDate' => date('c', strtotime('-9 days')),
        'remote' => true,
      ],
    ];

    return new JsonResponse($jobs);
  }

}
