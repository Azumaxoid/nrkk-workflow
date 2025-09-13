<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ApprovalFlow;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create organizations
        $organizations = [
            [
                'name' => '株式会社テクノロジー革新',
                'code' => 'TECH_INNOVATION',
                'description' => 'AI・IoT・ブロックチェーン技術を駆使した次世代ソリューション開発企業',
            ],
            [
                'name' => '株式会社グリーンエネルギー',
                'code' => 'GREEN_ENERGY',
                'description' => '持続可能な再生可能エネルギーシステムの開発・運営企業',
            ],
            [
                'name' => 'やまと建設株式会社',
                'code' => 'YAMATO_KENSETSU',
                'description' => '地域密着型の総合建設業として70年の歴史を持つ老舗企業',
            ],
            [
                'name' => 'みどり食品工業株式会社',
                'code' => 'MIDORI_FOOD',
                'description' => '伝統的な日本の味を守る食品製造業、全国に展開する老舗メーカー',
            ],
            [
                'name' => 'さくら運輸株式会社',
                'code' => 'SAKURA_UNYU',
                'description' => '昭和30年創業の運輸業界のパイオニア、地方物流ネットワークを支える',
            ],
            [
                'name' => '株式会社フィンテック',
                'code' => 'FINTECH',
                'description' => '金融テクノロジーと暗号資産の革新的サービス提供',
            ],
            [
                'name' => '株式会社エデュテック',
                'code' => 'EDUTECH',
                'description' => 'AI教育プラットフォームと個人最適化学習システム',
            ],
            [
                'name' => '株式会社アグリテック',
                'code' => 'AGRI_TECH',
                'description' => 'スマート農業とサステナブルフード産業の推進',
            ],
            [
                'name' => '株式会社ロボティクス',
                'code' => 'ROBOTICS',
                'description' => '産業用ロボットとAI自動化システムの設計・製造',
            ],
            [
                'name' => '株式会社クラウドインフラ',
                'code' => 'CLOUD_INFRA',
                'description' => 'エンタープライズクラウド基盤とセキュリティサービス',
            ],
        ];

        $createdOrganizations = [];
        foreach ($organizations as $orgData) {
            $createdOrganizations[] = Organization::create($orgData);
        }

        // Create admin user
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@wf.nrkk.technology',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => '管理部',
            'position' => '管理者',
            'organization_id' => $createdOrganizations[0]->id,
            'slack_webhook_url' => 'https://hooks.slack.com/triggers/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',
            'notification_preferences' => ['email', 'slack'],
        ]);

        // Test users for Selenium
        $testApplicants = [
            ['name' => '星野和子', 'email' => 'hoshino.kazuko@wf.nrkk.technology'],
            ['name' => '笹田純子', 'email' => 'sasada.junko@wf.nrkk.technology'],
            ['name' => '斉藤和明', 'email' => 'saito.kazuaki@wf.nrkk.technology'],
        ];

        $testApprovers = [
            ['name' => '中村恵子', 'email' => 'nakamura.keiko@wf.nrkk.technology'],
            ['name' => '木村智子', 'email' => 'kimura.tomoko@wf.nrkk.technology'],
        ];

        // Create test applicants
        foreach ($testApplicants as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role' => 'applicant',
                'department' => '一般部',
                'position' => '一般社員',
                'organization_id' => $createdOrganizations[0]->id,
                'notification_preferences' => ['email'],
            ]);
        }

        // Create test approvers
        foreach ($testApprovers as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role' => 'approver',
                'department' => '承認部',
                'position' => '課長',
                'organization_id' => $createdOrganizations[0]->id,
                'notification_preferences' => ['email'],
            ]);
        }

        // Japanese names with romanized emails
        $approverData = [
            ['name' => '佐藤太郎', 'email' => 'sato.taro@wf.nrkk.technology'],
            ['name' => '鈴木花子', 'email' => 'suzuki.hanako@wf.nrkk.technology'],
            ['name' => '高橋一郎', 'email' => 'takahashi.ichiro@wf.nrkk.technology'],
            ['name' => '田中美紀', 'email' => 'tanaka.miki@wf.nrkk.technology'],
            ['name' => '伊藤健太', 'email' => 'ito.kenta@wf.nrkk.technology'],
            ['name' => '渡辺由美', 'email' => 'watanabe.yumi@wf.nrkk.technology'],
            ['name' => '山本直樹', 'email' => 'yamamoto.naoki@wf.nrkk.technology'],
            ['name' => '中村恵子', 'email' => 'nakamura.keiko@wf.nrkk.technology'],
            ['name' => '小林修', 'email' => 'kobayashi.osamu@wf.nrkk.technology'],
            ['name' => '加藤雅子', 'email' => 'kato.masako@wf.nrkk.technology'],
            ['name' => '吉田博文', 'email' => 'yoshida.hirofumi@wf.nrkk.technology'],
            ['name' => '山田明美', 'email' => 'yamada.akemi@wf.nrkk.technology'],
            ['name' => '佐々木良太', 'email' => 'sasaki.ryota@wf.nrkk.technology'],
            ['name' => '松本千春', 'email' => 'matsumoto.chiharu@wf.nrkk.technology'],
            ['name' => '井上裕介', 'email' => 'inoue.yusuke@wf.nrkk.technology'],
            ['name' => '木村智子', 'email' => 'kimura.tomoko@wf.nrkk.technology'],
            ['name' => '林大輔', 'email' => 'hayashi.daisuke@wf.nrkk.technology'],
            ['name' => '斎藤真理', 'email' => 'saito.mari@wf.nrkk.technology'],
            ['name' => '清水俊夫', 'email' => 'shimizu.toshio@wf.nrkk.technology'],
            ['name' => '山口綾香', 'email' => 'yamaguchi.ayaka@wf.nrkk.technology']
        ];

        $applicantData = [
            ['name' => '青木翔太', 'email' => 'aoki.shota@wf.nrkk.technology'],
            ['name' => '石川由紀', 'email' => 'ishikawa.yuki@wf.nrkk.technology'],
            ['name' => '上田拓也', 'email' => 'ueda.takuya@wf.nrkk.technology'],
            ['name' => '江川舞', 'email' => 'egawa.mai@wf.nrkk.technology'],
            ['name' => '大野雄一', 'email' => 'ono.yuichi@wf.nrkk.technology'],
            ['name' => '岡田沙織', 'email' => 'okada.saori@wf.nrkk.technology'],
            ['name' => '片山健司', 'email' => 'katayama.kenji@wf.nrkk.technology'],
            ['name' => '川口美穂', 'email' => 'kawaguchi.miho@wf.nrkk.technology'],
            ['name' => '木下隆史', 'email' => 'kinoshita.takashi@wf.nrkk.technology'],
            ['name' => '小松恵理', 'email' => 'komatsu.eri@wf.nrkk.technology'],
            ['name' => '斉藤和明', 'email' => 'saito.kazuaki@wf.nrkk.technology'],
            ['name' => '酒井梨花', 'email' => 'sakai.rika@wf.nrkk.technology'],
            ['name' => '坂本勝彦', 'email' => 'sakamoto.katsuhiko@wf.nrkk.technology'],
            ['name' => '笹田純子', 'email' => 'sasada.junko@wf.nrkk.technology'],
            ['name' => '島田光男', 'email' => 'shimada.mitsuo@wf.nrkk.technology'],
            ['name' => '杉山典子', 'email' => 'sugiyama.noriko@wf.nrkk.technology'],
            ['name' => '関口哲也', 'email' => 'sekiguchi.tetsuya@wf.nrkk.technology'],
            ['name' => '高木真由美', 'email' => 'takagi.mayumi@wf.nrkk.technology'],
            ['name' => '竹内浩二', 'email' => 'takeuchi.koji@wf.nrkk.technology'],
            ['name' => '田村香織', 'email' => 'tamura.kaori@wf.nrkk.technology'],
            ['name' => '千葉正樹', 'email' => 'chiba.masaki@wf.nrkk.technology'],
            ['name' => '土屋美加', 'email' => 'tsuchiya.mika@wf.nrkk.technology'],
            ['name' => '寺田慎一', 'email' => 'terada.shinichi@wf.nrkk.technology'],
            ['name' => '中川麻衣', 'email' => 'nakagawa.mai@wf.nrkk.technology'],
            ['name' => '永田雅人', 'email' => 'nagata.masato@wf.nrkk.technology'],
            ['name' => '中島美智子', 'email' => 'nakajima.michiko@wf.nrkk.technology'],
            ['name' => '新田健一', 'email' => 'nitta.kenichi@wf.nrkk.technology'],
            ['name' => '西村由里', 'email' => 'nishimura.yuri@wf.nrkk.technology'],
            ['name' => '野村大介', 'email' => 'nomura.daisuke@wf.nrkk.technology'],
            ['name' => '橋本千恵子', 'email' => 'hashimoto.chieko@wf.nrkk.technology'],
            ['name' => '長谷川俊介', 'email' => 'hasegawa.shunsuke@wf.nrkk.technology'],
            ['name' => '浜田真紀', 'email' => 'hamada.maki@wf.nrkk.technology'],
            ['name' => '原田昌幸', 'email' => 'harada.masayuki@wf.nrkk.technology'],
            ['name' => '東真理子', 'email' => 'higashi.mariko@wf.nrkk.technology'],
            ['name' => '平野浩司', 'email' => 'hirano.koji@wf.nrkk.technology'],
            ['name' => '福田恵美', 'email' => 'fukuda.emi@wf.nrkk.technology'],
            ['name' => '藤井秀樹', 'email' => 'fujii.hideki@wf.nrkk.technology'],
            ['name' => '星野和子', 'email' => 'hoshino.kazuko@wf.nrkk.technology'],
            ['name' => '前田康雄', 'email' => 'maeda.yasuo@wf.nrkk.technology'],
            ['name' => '増田美穂子', 'email' => 'masuda.mihoko@wf.nrkk.technology'],
            ['name' => '松田隆之', 'email' => 'matsuda.takayuki@wf.nrkk.technology'],
            ['name' => '丸山典子', 'email' => 'maruyama.noriko@wf.nrkk.technology'],
            ['name' => '水野浩一', 'email' => 'mizuno.koichi@wf.nrkk.technology'],
            ['name' => '宮田由香', 'email' => 'miyata.yuka@wf.nrkk.technology'],
            ['name' => '森下誠一', 'email' => 'morishita.seiichi@wf.nrkk.technology'],
            ['name' => '安田優子', 'email' => 'yasuda.yuko@wf.nrkk.technology'],
            ['name' => '山下拓郎', 'email' => 'yamashita.takuro@wf.nrkk.technology'],
            ['name' => '横田美奈', 'email' => 'yokota.mina@wf.nrkk.technology'],
            ['name' => '吉川雅志', 'email' => 'yoshikawa.masashi@wf.nrkk.technology'],
            ['name' => '若林恵理', 'email' => 'wakabayashi.eri@wf.nrkk.technology']
        ];

        $notificationTypes = [
            ['email'],
            ['slack'],
            ['email', 'slack']
        ];

        // Create users for each organization
        $allApprovers = [];
        $allApplicants = [];

        foreach ($createdOrganizations as $index => $org) {
            // Create 1-3 approvers per organization
            $approverCount = rand(1, 3);
            $orgApprovers = [];
            
            for ($i = 0; $i < $approverCount; $i++) {
                $approverInfo = $approverData[array_rand($approverData)];
                $email = $approverInfo['email'] . '.org' . $index . '.a' . $i . '@wf.nrkk.technology';
                $notificationPref = $notificationTypes[array_rand($notificationTypes)];
                
                $userData = [
                    'name' => $approverInfo['name'],
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'approver',
                    'department' => '承認部',
                    'position' => '課長',
                    'organization_id' => $org->id,
                    'notification_preferences' => $notificationPref,
                ];

                if (in_array('slack', $notificationPref)) {
                    $userData['slack_webhook_url'] = 'https://hooks.slack.com/triggers/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';
                }

                $approver = User::create($userData);
                $orgApprovers[] = $approver;
                $allApprovers[] = $approver;
            }

            // Create 10-30 applicants per organization
            $applicantCount = rand(10, 30);
            
            for ($i = 0; $i < $applicantCount; $i++) {
                $applicantInfo = $applicantData[array_rand($applicantData)];
                $email = $applicantInfo['email'] . '.org' . $index . '.u' . $i . '@wf.nrkk.technology';
                $notificationPref = $notificationTypes[array_rand($notificationTypes)];
                
                $userData = [
                    'name' => $applicantInfo['name'],
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'applicant',
                    'department' => '一般部',
                    'position' => '一般社員',
                    'organization_id' => $org->id,
                    'notification_preferences' => $notificationPref,
                ];

                if (in_array('slack', $notificationPref)) {
                    $userData['slack_webhook_url'] = 'https://hooks.slack.com/triggers/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';
                }

                $applicant = User::create($userData);
                $allApplicants[] = $applicant;
            }

            // Create approval flow for each organization
            ApprovalFlow::create([
                'name' => $org->name . '用承認フロー',
                'description' => $org->name . '専用の承認ワークフロー',
                'application_type' => 'other',
                'organization_id' => $org->id,
                'step_count' => 2,
                'flow_config' => [
                    0 => [
                        'type' => 'review',
                        'approvers' => array_map(function($approver) { return $approver->id; }, $orgApprovers),
                        'approval_mode' => count($orgApprovers) > 1 ? 'any_one' : 'all',
                    ],
                    1 => [
                        'type' => 'approve',
                        'approvers' => [$admin->id],
                    ],
                ],
                'is_active' => true,
            ]);
        }

        // Create test applicants (exact names for UI test)
        $testApplicants = [
            ['name' => '星野和子', 'email' => 'hoshino.kazuko@wf.nrkk.technology'],
            ['name' => '笹田純子', 'email' => 'sasada.junko@wf.nrkk.technology'],
            ['name' => '斉藤和明', 'email' => 'saito.kazuaki@wf.nrkk.technology'],
        ];
        
        $testApprovers = [
            ['name' => '中村恵子', 'email' => 'nakamura.keiko@wf.nrkk.technology'],
            ['name' => '木村智子', 'email' => 'kimura.tomoko@wf.nrkk.technology'],
        ];
        
        $testOrg = $createdOrganizations[0];
        
        // Create test applicant users
        $createdTestApplicants = [];
        foreach ($testApplicants as $applicant) {
            $existingUser = User::where('email', $applicant['email'])->first();
            if (!$existingUser) {
                $testUser = User::create([
                    'name' => $applicant['name'],
                    'email' => $applicant['email'],
                    'password' => Hash::make('password'),
                    'role' => 'applicant',
                    'department' => 'テスト部',
                    'position' => '一般',
                    'organization_id' => $testOrg->id,
                    'notification_preferences' => ['email'],
                ]);
                $createdTestApplicants[] = $testUser;
            } else {
                // Update existing user to be in test org
                $existingUser->update([
                    'role' => 'applicant',
                    'organization_id' => $testOrg->id,
                ]);
                $createdTestApplicants[] = $existingUser;
            }
        }
        
        // Create test approver users
        $createdTestApprovers = [];
        foreach ($testApprovers as $approver) {
            $existingApprover = User::where('email', $approver['email'])->first();
            if (!$existingApprover) {
                $testApprover = User::create([
                    'name' => $approver['name'],
                    'email' => $approver['email'],
                    'password' => Hash::make('password'),
                    'role' => 'approver',
                    'department' => '承認部',
                    'position' => '課長',
                    'organization_id' => $testOrg->id,
                    'notification_preferences' => ['email'],
                ]);
                $createdTestApprovers[] = $testApprover;
            } else {
                // Update existing user to be in test org
                $existingApprover->update([
                    'role' => 'approver',
                    'organization_id' => $testOrg->id,
                ]);
                $createdTestApprovers[] = $existingApprover;
            }
        }
        
        // Create approval flow for test organization
        $testFlow = \App\Models\ApprovalFlow::create([
            'name' => 'テスト承認フロー',
            'organization_id' => $testOrg->id,
            'application_type' => 'other',
            'flow_config' => [
                0 => [
                    'type' => 'approve',
                    'approvers' => array_map(function($approver) { return $approver->id; }, $createdTestApprovers),
                    'approval_mode' => 'any_one',
                ],
            ],
            'step_count' => 1,
            'is_active' => true,
        ]);

        // Create sample applications with approved status
        if (!empty($createdTestApplicants)) {
            for ($i = 0; $i < 5; $i++) {
                $applicant = $createdTestApplicants[$i % count($createdTestApplicants)];
                
                $sampleApp = \App\Models\Application::create([
                    'title' => "承認済みテスト申請 " . ($i + 1),
                    'description' => 'システムテスト用の承認済みサンプル申請です。',
                    'type' => 'other',
                    'priority' => 'medium',
                    'applicant_id' => $applicant->id,
                    'status' => 'approved',
                    'approval_flow_id' => $testFlow->id,
                    'due_date' => now()->addDays(7),
                ]);

                // Create approved approval record
                $approver = $createdTestApprovers[0]; // Use first test approver
                \App\Models\Approval::create([
                    'application_id' => $sampleApp->id,
                    'approver_id' => $approver->id,
                    'approval_flow_id' => $testFlow->id,
                    'step_number' => 0,
                    'step_type' => 'approve',
                    'status' => 'approved',
                    'comment' => 'シーダーで自動承認されました',
                    'acted_at' => now(),
                ]);
            }
        }
        
        // Also create some sample applications for general users if they exist
        if (!empty($allApplicants)) {
            $sampleApp = \App\Models\Application::create([
                'title' => 'テスト申請',
                'description' => 'システムテスト用のサンプル申請です。',
                'type' => 'other',
                'priority' => 'medium',
                'applicant_id' => $allApplicants[0]->id,
                'status' => 'under_review',
                'due_date' => now()->addDays(7),
            ]);

            // Set approval flow
            $generalFlow = \App\Models\ApprovalFlow::where('organization_id', $allApplicants[0]->organization_id)->first();
            if ($generalFlow) {
                $sampleApp->update(['approval_flow_id' => $generalFlow->id]);
                $generalFlow->createApprovals($sampleApp);

                // Send notifications
                $notificationService = new \App\Services\NotificationService();
                $notificationService->applicationSubmitted($sampleApp);
            }
        }
    }
}