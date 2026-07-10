<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedSearchData extends Command
{
    protected $signature = 'dev:seed-search {--fresh : Wipe existing courses and videos first}';

    protected $description = 'Seed 10 realistic courses with 10 videos each for search testing';

    private array $courses = [
        [
            'title' => 'Python for Beginners',
            'description' => 'Start your programming journey with Python. Learn variables, loops, functions, and build your first real projects from scratch.',
            'tags' => ['python', 'programming', 'beginners'],
            'videos' => [
                ['title' => 'Setting Up Your Python Environment',          'description' => 'Install Python and configure VS Code for a smooth development experience.'],
                ['title' => 'Variables, Data Types and Operators',         'description' => 'Understand how Python stores and manipulates data with types like int, str, and bool.'],
                ['title' => 'Control Flow: if, elif, and else',            'description' => 'Make your programs make decisions with conditional statements.'],
                ['title' => 'Loops: for and while in Depth',               'description' => 'Iterate over sequences and repeat logic efficiently.'],
                ['title' => 'Functions and Scope',                         'description' => 'Write reusable blocks of code and understand how variable scope works.'],
                ['title' => 'Lists, Tuples and Dictionaries',              'description' => 'Master Python\'s built-in data structures for storing collections of data.'],
                ['title' => 'File I/O: Reading and Writing Files',         'description' => 'Open, read, write and close files using Python\'s built-in file handling.'],
                ['title' => 'Error Handling with try and except',          'description' => 'Write resilient programs that handle unexpected situations gracefully.'],
                ['title' => 'Introduction to Object-Oriented Programming', 'description' => 'Learn classes, objects, inheritance and encapsulation in Python.'],
                ['title' => 'Capstone: Build a CLI To-Do App',             'description' => 'Put everything together by building a command-line task manager.'],
            ],
        ],
        [
            'title' => "A Beginner's Guide to Kubernetes for Container Orchestration",
            'description' => 'Learn how Kubernetes works from the ground up. Deploy, scale and manage containerised applications like a platform engineer.',
            'tags' => ['kubernetes', 'devops', 'containers', 'cloud'],
            'videos' => [
                ['title' => 'Why Kubernetes? The Problem It Solves',         'description' => 'Understand the pain points of running containers at scale that Kubernetes addresses.'],
                ['title' => 'Kubernetes Architecture: Nodes, Pods and the Control Plane', 'description' => 'Break down the core components that make up a Kubernetes cluster.'],
                ['title' => 'Installing a Local Cluster with kind',          'description' => 'Spin up a fully functional Kubernetes cluster on your laptop in minutes.'],
                ['title' => 'Your First Deployment and Service',             'description' => 'Deploy an nginx container and expose it to traffic using a Service.'],
                ['title' => 'ConfigMaps and Secrets',                        'description' => 'Manage configuration and sensitive data separately from your container images.'],
                ['title' => 'Persistent Volumes and Storage',                'description' => 'Attach durable storage to Pods so data survives restarts.'],
                ['title' => 'Rolling Updates and Rollbacks',                 'description' => 'Deploy new versions of your app with zero downtime and roll back safely.'],
                ['title' => 'Horizontal Pod Autoscaling',                    'description' => 'Automatically scale your workloads up and down based on CPU and memory metrics.'],
                ['title' => 'Ingress Controllers and TLS Termination',       'description' => 'Route external HTTP traffic to the right services and terminate SSL at the edge.'],
                ['title' => 'Monitoring with Prometheus and Grafana',        'description' => 'Set up observability for your cluster using the industry-standard monitoring stack.'],
            ],
        ],
        [
            'title' => 'Docker from Zero to Production',
            'description' => 'Master containerisation with Docker. Build images, compose multi-service apps, and ship confidently to any environment.',
            'tags' => ['docker', 'devops', 'containers'],
            'videos' => [
                ['title' => 'What is Docker and Why It Matters',              'description' => 'Understand containers vs virtual machines and when Docker makes sense.'],
                ['title' => 'Installing Docker and Running Your First Container', 'description' => 'Get Docker running and pull your first image from Docker Hub.'],
                ['title' => 'Writing Your First Dockerfile',                  'description' => 'Build a custom image for a Node.js application step by step.'],
                ['title' => 'Layers, Caching and Image Optimisation',         'description' => 'Learn how Docker layers work and how to write Dockerfiles that build fast.'],
                ['title' => 'Volumes and Bind Mounts',                        'description' => 'Persist data and share files between your host and container.'],
                ['title' => 'Networking Between Containers',                   'description' => 'Connect multiple containers using bridge networks and DNS-based service discovery.'],
                ['title' => 'Docker Compose for Multi-Service Apps',           'description' => 'Define and run a full stack (app, database, cache) with a single YAML file.'],
                ['title' => 'Environment Variables and Secrets Management',    'description' => 'Pass configuration into containers safely without hardcoding credentials.'],
                ['title' => 'Multi-Stage Builds for Lean Production Images',   'description' => 'Shrink your final image size by separating build and runtime stages.'],
                ['title' => 'Pushing Images and Deploying to a Server',        'description' => 'Publish your image to a registry and pull it on a remote server.'],
            ],
        ],
        [
            'title' => 'React 18: Build Modern Web Applications',
            'description' => 'Learn React from component basics to advanced patterns. Build fast, interactive UIs using hooks, context, and the latest React 18 features.',
            'tags' => ['react', 'javascript', 'frontend', 'web'],
            'videos' => [
                ['title' => 'Thinking in Components',                      'description' => 'Understand the mental model behind React and how to decompose a UI.'],
                ['title' => 'JSX, Props and the Component Tree',           'description' => 'Write JSX, pass data between components and read the component hierarchy.'],
                ['title' => 'State with useState',                         'description' => 'Add interactivity to your components with React\'s primary state hook.'],
                ['title' => 'Side Effects with useEffect',                  'description' => 'Fetch data, set up subscriptions and clean up after your components.'],
                ['title' => 'Forms and Controlled Inputs',                  'description' => 'Handle user input and validate forms the React way.'],
                ['title' => 'Lifting State and Sharing Data',               'description' => 'Move state up the tree to share it between sibling components.'],
                ['title' => 'Context API for Global State',                 'description' => 'Avoid prop drilling by sharing state across the component tree.'],
                ['title' => 'React Router v6: Client-Side Navigation',      'description' => 'Build multi-page SPAs with nested routes, loaders and error boundaries.'],
                ['title' => 'Performance: useMemo, useCallback and React.memo', 'description' => 'Identify and fix unnecessary re-renders in your application.'],
                ['title' => 'Concurrent Features: Suspense and Transitions', 'description' => 'Use React 18 Suspense and startTransition to keep your UI responsive.'],
            ],
        ],
        [
            'title' => 'Laravel 11: Full-Stack PHP Development',
            'description' => 'Build production-ready web applications with Laravel. From routing and Eloquent ORM to queues, events and deployment.',
            'tags' => ['laravel', 'php', 'backend', 'web'],
            'videos' => [
                ['title' => 'Laravel Project Structure and Artisan CLI',      'description' => 'Navigate a Laravel application and use Artisan to generate code.'],
                ['title' => 'Routing, Middleware and Controllers',             'description' => 'Handle HTTP requests, protect routes and organise your controller logic.'],
                ['title' => 'Eloquent ORM: Models, Migrations and Relations',  'description' => 'Define your database schema in PHP and query it with expressive ORM syntax.'],
                ['title' => 'Blade Templating Engine',                        'description' => 'Build dynamic HTML views with Blade directives, components and slots.'],
                ['title' => 'Authentication with Laravel Breeze',              'description' => 'Add login, registration and password reset to your app in minutes.'],
                ['title' => 'Form Validation and Error Handling',              'description' => 'Validate request data and return meaningful error messages to users.'],
                ['title' => 'File Storage with S3 and Local Disks',           'description' => 'Upload, store and serve files using Laravel\'s unified filesystem API.'],
                ['title' => 'Queues, Jobs and Horizon',                       'description' => 'Defer expensive work to background jobs and monitor them with Horizon.'],
                ['title' => 'Events, Listeners and Notifications',            'description' => 'Decouple your application with events and notify users via email or Slack.'],
                ['title' => 'Testing with PHPUnit and Pest',                  'description' => 'Write feature and unit tests that give you confidence to ship.'],
            ],
        ],
        [
            'title' => 'AWS Solutions Architect Essentials',
            'description' => 'Understand the core AWS services and how to design scalable, resilient cloud architectures. Ideal preparation for the SAA-C03 exam.',
            'tags' => ['aws', 'cloud', 'architecture', 'devops'],
            'videos' => [
                ['title' => 'AWS Global Infrastructure: Regions and AZs',     'description' => 'Learn how AWS organises its global infrastructure and why it matters for resilience.'],
                ['title' => 'IAM: Users, Roles and Policies',                 'description' => 'Control who can do what in your AWS account with fine-grained permissions.'],
                ['title' => 'EC2: Virtual Machines in the Cloud',             'description' => 'Launch, configure and connect to EC2 instances and choose the right instance type.'],
                ['title' => 'S3: Object Storage Deep Dive',                   'description' => 'Store and retrieve files at any scale with S3 bucket policies and lifecycle rules.'],
                ['title' => 'VPC: Private Networks on AWS',                   'description' => 'Design a secure, multi-tier network with subnets, route tables and security groups.'],
                ['title' => 'RDS and Aurora: Managed Databases',              'description' => 'Run PostgreSQL and MySQL without managing the underlying infrastructure.'],
                ['title' => 'Load Balancers and Auto Scaling',                'description' => 'Distribute traffic and automatically adjust capacity to handle demand spikes.'],
                ['title' => 'CloudFront: Global Content Delivery',            'description' => 'Cache and serve your content from edge locations close to your users.'],
                ['title' => 'Lambda and Serverless Architectures',            'description' => 'Run code without provisioning servers and pay only for what you use.'],
                ['title' => 'Designing for High Availability and Disaster Recovery', 'description' => 'Apply the AWS Well-Architected Framework to build systems that stay up.'],
            ],
        ],
        [
            'title' => 'Git & GitHub: A Complete Developer Workflow',
            'description' => 'Go beyond git commit. Learn branching strategies, rebasing, pull request workflows, and how to collaborate effectively on any team.',
            'tags' => ['git', 'github', 'version-control', 'collaboration'],
            'videos' => [
                ['title' => 'How Git Works Under the Hood',                   'description' => 'Understand commits, trees and blobs — the objects that make Git tick.'],
                ['title' => 'Branching and Merging Strategies',               'description' => 'Compare Git Flow, GitHub Flow and trunk-based development.'],
                ['title' => 'Rebase vs Merge: When to Use Each',              'description' => 'Keep your history clean with rebase and avoid the pitfalls of rewriting shared history.'],
                ['title' => 'Interactive Rebase: Cleaning Up Commits',        'description' => 'Squash, reorder and reword commits before merging to keep the log readable.'],
                ['title' => 'Cherry-Pick and Stash',                         'description' => 'Apply individual commits across branches and temporarily shelve in-progress work.'],
                ['title' => 'Pull Requests: Code Review Best Practices',      'description' => 'Write clear PR descriptions, give useful feedback and use draft PRs effectively.'],
                ['title' => 'GitHub Actions: CI/CD Pipelines',                'description' => 'Automate testing, linting and deployment with GitHub\'s built-in workflow engine.'],
                ['title' => 'Protecting Branches and Enforcing Reviews',      'description' => 'Set up branch protection rules to prevent direct pushes to main.'],
                ['title' => 'Monorepos with Git Subtrees and Submodules',     'description' => 'Manage multiple projects in one repository without losing your mind.'],
                ['title' => 'Resolving Merge Conflicts Like a Pro',           'description' => 'Understand conflict markers and use tools like VS Code and git mergetool.'],
            ],
        ],
        [
            'title' => 'Introduction to Machine Learning with Python',
            'description' => 'Learn the fundamentals of machine learning using scikit-learn and pandas. Build and evaluate real models without a maths degree.',
            'tags' => ['machine-learning', 'python', 'data-science', 'ai'],
            'videos' => [
                ['title' => 'What is Machine Learning? Supervised vs Unsupervised', 'description' => 'Understand the landscape of ML and where each approach is used.'],
                ['title' => 'Data Exploration with pandas and matplotlib',    'description' => 'Load, inspect and visualise datasets before building any model.'],
                ['title' => 'Data Cleaning and Feature Engineering',          'description' => 'Handle missing values, outliers and transform raw features into model inputs.'],
                ['title' => 'Linear Regression from Scratch',                 'description' => 'Understand the maths behind linear regression and implement it in numpy.'],
                ['title' => 'Classification with Logistic Regression',        'description' => 'Predict categories and interpret probabilities and decision boundaries.'],
                ['title' => 'Decision Trees and Random Forests',              'description' => 'Build interpretable tree models and boost accuracy with ensemble methods.'],
                ['title' => 'Model Evaluation: Accuracy, Precision and Recall', 'description' => 'Choose the right metric for your problem and avoid misleading results.'],
                ['title' => 'Cross-Validation and Hyperparameter Tuning',    'description' => 'Tune models reliably without overfitting to your validation set.'],
                ['title' => 'Clustering with K-Means',                        'description' => 'Group unlabelled data and choose the optimal number of clusters.'],
                ['title' => 'Saving Models and Building a Prediction API',   'description' => 'Persist trained models and serve predictions over HTTP with FastAPI.'],
            ],
        ],
        [
            'title' => 'Mastering PostgreSQL for Application Developers',
            'description' => 'Go beyond basic SQL. Learn advanced queries, indexing strategies, JSON support, and performance tuning for production databases.',
            'tags' => ['postgresql', 'sql', 'database', 'backend'],
            'videos' => [
                ['title' => 'PostgreSQL Architecture: Processes and Storage',  'description' => 'Understand how Postgres handles connections, writes and reads at a high level.'],
                ['title' => 'Advanced SELECT: CTEs, Window Functions and FILTER', 'description' => 'Write expressive queries using WITH clauses and analytical functions.'],
                ['title' => 'Indexes Deep Dive: B-tree, GIN, GiST and BRIN',  'description' => 'Choose the right index type for your access patterns and avoid over-indexing.'],
                ['title' => 'EXPLAIN ANALYZE: Reading Query Plans',            'description' => 'Understand what Postgres actually does to run your query and spot bottlenecks.'],
                ['title' => 'Transactions, Isolation Levels and MVCC',        'description' => 'Learn how Postgres handles concurrency and which isolation level to use.'],
                ['title' => 'JSONB: Querying and Indexing Semi-Structured Data', 'description' => 'Store and query flexible data with Postgres\'s powerful JSONB column type.'],
                ['title' => 'Full-Text Search Built into Postgres',           'description' => 'Add fast text search without an external engine using tsvector and tsquery.'],
                ['title' => 'Partitioning Large Tables',                      'description' => 'Split huge tables by range or list to keep queries fast as data grows.'],
                ['title' => 'Replication: Streaming and Logical',             'description' => 'Set up read replicas and logical replication for high availability.'],
                ['title' => 'Connection Pooling with PgBouncer',              'description' => 'Prevent connection exhaustion at scale by pooling with PgBouncer.'],
            ],
        ],
        [
            'title' => 'TypeScript: From JavaScript to Type Safety',
            'description' => 'Add static types to your JavaScript codebase. Learn TypeScript from basic annotations to advanced generics and utility types.',
            'tags' => ['typescript', 'javascript', 'frontend', 'web'],
            'videos' => [
                ['title' => 'Why TypeScript? The Problems It Solves',         'description' => 'See how TypeScript catches bugs at compile time that JavaScript misses at runtime.'],
                ['title' => 'Basic Types, Interfaces and Type Aliases',       'description' => 'Annotate variables, function parameters and return values with TypeScript types.'],
                ['title' => 'Union Types, Intersection Types and Narrowing',  'description' => 'Model complex data shapes and write code that handles multiple possible types.'],
                ['title' => 'Generics: Writing Reusable Typed Code',          'description' => 'Create functions and classes that work with any type while keeping type safety.'],
                ['title' => 'Enums and Literal Types',                        'description' => 'Represent fixed sets of values and avoid magic strings in your codebase.'],
                ['title' => 'Classes, Access Modifiers and Decorators',       'description' => 'Use TypeScript\'s class features to write object-oriented code with strong contracts.'],
                ['title' => 'Utility Types: Partial, Pick, Omit and more',   'description' => 'Transform existing types without repeating yourself using built-in utility types.'],
                ['title' => 'Type Guards and Discriminated Unions',           'description' => 'Write runtime checks that TypeScript understands and uses to narrow types.'],
                ['title' => 'TypeScript with React: Props, Hooks and Events', 'description' => 'Type your React components, custom hooks and DOM event handlers correctly.'],
                ['title' => 'Migrating a JavaScript Project to TypeScript',   'description' => 'Incrementally add TypeScript to an existing JS project without a full rewrite.'],
            ],
        ],
    ];

    public function handle(): int
    {
        if ($this->option('fresh')) {
            Video::query()->delete();
            Course::query()->delete();
            $this->line('Wiped existing courses and videos.');
        }

        $bar = $this->output->createProgressBar(count($this->courses));
        $bar->start();

        foreach ($this->courses as $courseData) {
            $videos = $courseData['videos'];
            unset($courseData['videos']);

            $courseData['slug'] = Str::slug($courseData['title']);
            $courseData['is_published'] = true;
            $courseData['published_at'] = now()->subDays(rand(1, 180));

            $course = Course::create($courseData);

            foreach ($videos as $position => $videoData) {
                $course->videos()->create([
                    'title' => $videoData['title'],
                    'description' => $videoData['description'],
                    'duration_seconds' => rand(300, 3600),
                    'is_published' => true,
                    'published_at' => $course->published_at->addDays($position),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Seeded 10 courses with 10 videos each.');
        $this->newLine();
        $this->line('Run the following to push them into Elasticsearch:');
        $this->line('  php artisan scout:import "App\Models\Course"');
        $this->line('  php artisan scout:import "App\Models\Video"');

        return self::SUCCESS;
    }
}
