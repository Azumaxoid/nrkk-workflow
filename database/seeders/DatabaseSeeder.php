<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Organization;
use App\Models\ApprovalFlow;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create organizations
        $organizations = [
            ['name' => '株式会社テックイノベーション', 'code' => 'TECH_INNOVATION', 'description' => 'AI・IoT・ブロックチェーン技術を駆使した次世代ソリューション開発企業'],
            ['name' => '株式会社グリーンエネルギー', 'code' => 'GREEN_ENERGY', 'description' => '持続可能な再生可能エネルギーシステムの開発・運営企業'],
            ['name' => 'やまと建設株式会社', 'code' => 'YAMATO_KENSETSU', 'description' => '地域密着型の総合建設業として70年の歴史を持つ老舗企業'],
            ['name' => 'みどり食品工業株式会社', 'code' => 'MIDORI_FOOD', 'description' => '伝統的な日本の味を守る食品製造業、全国に展開する老舗メーカー'],
            ['name' => 'さくら運輸株式会社', 'code' => 'SAKURA_UNYU', 'description' => '昭和30年創業の運輸業界のパイオニア、地方物流ネットワークを支える'],
            ['name' => '株式会社フィンテック', 'code' => 'FINTECH', 'description' => '金融テクノロジーと暗号資産の革新的サービス提供'],
            ['name' => '株式会社エデュテック', 'code' => 'EDUTECH', 'description' => 'AI教育プラットフォームと個人最適化学習システム'],
            ['name' => '株式会社アグリテック', 'code' => 'AGRI_TECH', 'description' => 'スマート農業とサステナブルフード産業の推進'],
            ['name' => '株式会社ロボティクス', 'code' => 'ROBOTICS', 'description' => '産業用ロボットとAI自動化システムの設計・製造'],
            ['name' => '株式会社クラウドインフラ', 'code' => 'CLOUD_INFRA', 'description' => 'エンタープライズクラウド基盤とセキュリティサービス'],
        ];

        $createdOrganizations = [];
        foreach ($organizations as $orgData) {
            $createdOrganizations[] = Organization::updateOrCreate(
                ['code' => $orgData['code']],
                $orgData
            );
        }

        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@wf.nrkk.technology'],
            [
                'name' => '管理者',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department' => '管理部',
                'position' => '管理者',
                'organization_id' => $createdOrganizations[0]->id,
                'notification_preferences' => ['email'],
            ]
        );

        // テスト用ユーザー（各組織に1人ずつ）
        $testUsers = [
            // 申請者
            ['name' => '星野和子', 'email' => 'hoshino.kazuko@wf.nrkk.technology', 'role' => 'applicant', 'org' => 1],
            ['name' => '笹田純子', 'email' => 'sasada.junko@wf.nrkk.technology', 'role' => 'applicant', 'org' => 1],
            ['name' => '斉藤和明', 'email' => 'saito.kazuaki@wf.nrkk.technology', 'role' => 'applicant', 'org' => 2],
            ['name' => '青木翔太', 'email' => 'aoki.shota@wf.nrkk.technology', 'role' => 'applicant', 'org' => 3],
            ['name' => '石川由紀', 'email' => 'ishikawa.yuki@wf.nrkk.technology', 'role' => 'applicant', 'org' => 4],
            ['name' => '上田拓也', 'email' => 'ueda.takuya@wf.nrkk.technology', 'role' => 'applicant', 'org' => 5],
            ['name' => '江川舞', 'email' => 'egawa.mai@wf.nrkk.technology', 'role' => 'applicant', 'org' => 6],
            ['name' => '大野雄一', 'email' => 'ono.yuichi@wf.nrkk.technology', 'role' => 'applicant', 'org' => 7],
            ['name' => '岡田沙織', 'email' => 'okada.saori@wf.nrkk.technology', 'role' => 'applicant', 'org' => 8],
            ['name' => '片山健司', 'email' => 'katayama.kenji@wf.nrkk.technology', 'role' => 'applicant', 'org' => 9],
            ['name' => '川口美穂', 'email' => 'kawaguchi.miho@wf.nrkk.technology', 'role' => 'applicant', 'org' => 10],

            // 新規申請者（バグテスト用） - 組織2,3,4に2名ずつ追加
            ['name' => '小林大輔', 'email' => 'kobayashi.daisuke@wf.nrkk.technology', 'role' => 'applicant', 'org' => 2],
            ['name' => '松田亜美', 'email' => 'matsuda.ami@wf.nrkk.technology', 'role' => 'applicant', 'org' => 2],
            ['name' => '橋本隆司', 'email' => 'hashimoto.takashi@wf.nrkk.technology', 'role' => 'applicant', 'org' => 3],
            ['name' => '福田麻衣', 'email' => 'fukuda.mai@wf.nrkk.technology', 'role' => 'applicant', 'org' => 3],
            ['name' => '森田健介', 'email' => 'morita.kensuke@wf.nrkk.technology', 'role' => 'applicant', 'org' => 4],
            ['name' => '吉田愛子', 'email' => 'yoshida.aiko@wf.nrkk.technology', 'role' => 'applicant', 'org' => 4],

            // 承認者
            ['name' => '中村恵子', 'email' => 'nakamura.keiko@wf.nrkk.technology', 'role' => 'approver', 'org' => 1],
            ['name' => '木村智子', 'email' => 'kimura.tomoko@wf.nrkk.technology', 'role' => 'approver', 'org' => 2],
            ['name' => '佐藤太郎', 'email' => 'sato.taro@wf.nrkk.technology', 'role' => 'approver', 'org' => 4],
            ['name' => '鈴木花子', 'email' => 'suzuki.hanako@wf.nrkk.technology', 'role' => 'approver', 'org' => 5],
            ['name' => '高橋一郎', 'email' => 'takahashi.ichiro@wf.nrkk.technology', 'role' => 'approver', 'org' => 6],
            ['name' => '田中美紀', 'email' => 'tanaka.miki@wf.nrkk.technology', 'role' => 'approver', 'org' => 7],
            ['name' => '伊藤健太', 'email' => 'ito.kenta@wf.nrkk.technology', 'role' => 'approver', 'org' => 8],
            ['name' => '渡辺由美', 'email' => 'watanabe.yumi@wf.nrkk.technology', 'role' => 'approver', 'org' => 9],
            ['name' => '山本直樹', 'email' => 'yamamoto.naoki@wf.nrkk.technology', 'role' => 'approver', 'org' => 10],
        ];

        // テスト用ユーザーを作成
        $createdApprovers = [];
        foreach ($testUsers as $testUser) {
            $orgIndex = $testUser['org'] - 1;
            $organization = $createdOrganizations[$orgIndex];

            $userData = [
                'name' => $testUser['name'],
                'email' => $testUser['email'],
                'password' => Hash::make('password'),
                'role' => $testUser['role'],
                'department' => $testUser['role'] === 'approver' ? '承認部' : '一般部',
                'position' => $testUser['role'] === 'approver' ? '課長' : '一般社員',
                'organization_id' => $organization->id,
                'notification_preferences' => ['email'],
            ];

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if ($testUser['role'] === 'approver') {
                $createdApprovers[] = ['user' => $user, 'org' => $organization];
            }
        }

        // 各組織に承認フローを作成
        foreach ($createdApprovers as $approverData) {
            $org = $approverData['org'];
            $approver = $approverData['user'];

            ApprovalFlow::create([
                'name' => $org->name . ' 承認フロー',
                'organization_id' => $org->id,
                'application_type' => 'other',
                'flow_config' => [
                    0 => [
                        'type' => 'approve',
                        'approvers' => [$approver->id],
                        'approval_mode' => 'any_one',
                    ],
                ],
                'step_count' => 1,
                'is_active' => true,
            ]);
        }

        echo "\n✅ DatabaseSeeder completed successfully!\n";
        echo "📊 Created:\n";
        echo "   - " . count($createdOrganizations) . " organizations\n";
        echo "   - " . User::count() . " users\n";
        echo "   - " . ApprovalFlow::count() . " approval flows\n";
    }
}