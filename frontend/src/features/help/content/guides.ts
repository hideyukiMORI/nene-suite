import type { Guide, GuideGroup } from './types'

/** Display labels for guide groups (rendered as sidebar / index headings). */
export const GUIDE_GROUPS: Readonly<Record<GuideGroup, string>> = {
  start: 'はじめに',
  screens: '画面ごとの使い方',
  tutorial: 'チュートリアル',
}

/** Order in which groups are listed. */
export const GUIDE_GROUP_ORDER: readonly GuideGroup[] = ['start', 'screens', 'tutorial']

/**
 * In-app guides, Japanese-first. Written for operators who are not used to
 * computers or the internet: short sentences, concrete steps, plain words first
 * and the technical identifier in parentheses.
 */
export const GUIDES: readonly Guide[] = [
  {
    slug: 'getting-started',
    title: 'はじめに — NeNe Suite とは',
    group: 'start',
    summary: 'NeNe Suite が何をするものか、最初の流れをやさしく説明します。',
    sections: [
      {
        heading: 'NeNe Suite でできること',
        paragraphs: [
          'NeNe Suite は、いくつかの NeNe アプリ（請求の Invoice、消込の Clear など）を 1 台のサーバーにまとめて入れて、ひとつのログインで使えるようにする「土台」です。',
          'Suite 自体は請求や帳簿などの作業はしません。必要なアプリを選んで入れ、入口（apex）と管理画面を用意します。',
        ],
      },
      {
        heading: '基本の流れ',
        steps: [
          'インストーラで、使いたいアプリを選びます。',
          'アプリごとにデータの保存先（データベース）を決めます。',
          '免責事項（disclaimer）を読んで同意します。',
          '内容を確認して、インストールを完了します。',
          'ログイン後の玄関画面（apex）から各アプリを開きます。',
        ],
        notes: [
          {
            tone: 'neutral',
            text: 'わからない言葉が出てきたら、用語集でいつでも調べられます。各画面の右上にある「このページの使い方」も活用してください。',
          },
        ],
      },
    ],
    related: ['concepts', 'install-wizard'],
  },
  {
    slug: 'concepts',
    title: '基本の用語と考え方',
    group: 'start',
    summary: '組織・メンバー・役割・アプリ・データベースなど、最初に押さえたい言葉を整理します。',
    sections: [
      {
        heading: '人と組織',
        bullets: [
          'organization（組織）… 利用する会社・事業者の単位です。',
          'operator（オペレーター）… ログインして操作する人です。',
          'membership（メンバーシップ）… その人がどの組織に、どの役割で属するかです。',
          'role（役割）… できることの範囲。Admin（管理）・Member（一般）・Viewer（閲覧）。',
        ],
      },
      {
        heading: 'アプリとデータ',
        bullets: [
          'sibling（兄弟アプリ）… Suite で導入できる個々の NeNe 製品です。',
          'catalog（カタログ）… 導入できるアプリの一覧と依存関係です。',
          'database target（データベースの割り当て）… 各アプリがどのデータベースを使うかです。',
        ],
        notes: [
          {
            tone: 'neutral',
            text: 'アプリごとにデータベースは分かれています。ひとつのアプリの不調が、別のアプリの帳簿を壊すことはありません。',
          },
        ],
      },
    ],
    related: ['roles', 'getting-started'],
  },
  {
    slug: 'roles',
    title: '権限（役割）の違い',
    group: 'start',
    summary: 'superadmin・Admin・Member・Viewer がそれぞれ何をできるかをまとめます。',
    sections: [
      {
        heading: '役割ごとにできること',
        paragraphs: ['役割（role）によって、見える画面とできる操作が変わります。'],
        table: {
          headers: ['役割', '主にできること'],
          rows: [
            ['superadmin', 'プラットフォーム全体の管理。組織の作成・無効化、メンバー管理など。'],
            ['Admin', '所属する組織の中の管理。メンバーの追加・役割変更など。'],
            ['Member', '日常の操作。割り当てられたアプリを使えます。'],
            ['Viewer', '閲覧のみ。変更はできません。'],
          ],
        },
        notes: [
          {
            tone: 'warning',
            text: '役割は必要最小限にしましょう。とくに superadmin は、本当に全体管理が必要な人だけに付けてください。',
          },
        ],
      },
    ],
    related: ['concepts', 'safety'],
  },
  {
    slug: 'safety',
    title: '安全に使うために',
    group: 'start',
    summary: '取り消せない操作・秘密情報・バックアップなど、気をつけたい点をまとめます。',
    sections: [
      {
        heading: '取り消せない操作に注意',
        bullets: [
          '組織の無効化や、メンバーの削除は、影響が大きい操作です。実行前に確認画面をよく読んでください。',
        ],
        notes: [
          {
            tone: 'danger',
            text: '操作の前に「これは元に戻せるか？」を一度考える習慣をつけましょう。迷ったら管理者に相談してください。',
          },
        ],
      },
      {
        heading: '秘密情報とバックアップ',
        bullets: [
          'パスワードやトークンは、画面の入力欄やメモに残さないでください。',
          '大事な操作の前後では、各アプリ側でデータのバックアップを取りましょう。',
        ],
        notes: [
          {
            tone: 'warning',
            text: 'NeNe Suite は技術的な導入支援のみで、業務・法務・会計の正しさは保証しません。判断に迷う場合は税理士・公認会計士・弁護士などの専門家にご相談ください。',
          },
        ],
      },
    ],
    related: ['roles', 'faq'],
  },
  {
    slug: 'faq',
    title: 'よくある質問',
    group: 'start',
    summary: '困ったときに最初に見る、短い質問と答えの集まりです。',
    sections: [
      {
        heading: 'ログインできません',
        paragraphs: [
          'メールアドレスとパスワードをもう一度確認してください。それでも入れない場合は、インストールを行った管理者にお問い合わせください。',
        ],
      },
      {
        heading: 'アプリが一覧に出てきません',
        paragraphs: [
          'そのアプリがまだインストールされていない可能性があります。インストーラから追加できます（権限が必要な場合があります）。',
        ],
      },
      {
        heading: '言葉の意味がわかりません',
        paragraphs: ['用語集に、やさしい説明をまとめています。画面の言葉から引くこともできます。'],
      },
    ],
    related: ['getting-started', 'safety'],
  },
  {
    slug: 'install-wizard',
    title: 'アプリのインストール手順',
    group: 'screens',
    summary: 'アプリ選択 → データベース → 同意 → 確認 → 完了の 5 ステップを順に説明します。',
    sections: [
      {
        heading: '1. アプリを選ぶ',
        paragraphs: [
          '使いたいアプリにチェックを入れます。あるアプリが別のアプリを必要とする場合（依存関係）は、必要なものが自動で一緒に選ばれ、正しい順番で入ります。',
        ],
      },
      {
        heading: '2. データベースを決める',
        paragraphs: ['アプリごとに、データの保存先を選びます。'],
        bullets: [
          'provision（新規作成）… Suite が新しいデータベースを作ります。とくに事情がなければこちら。',
          'adopt（既存採用）… すでにあるデータベースをそのまま登録して使います。Suite は中身を変えません。',
        ],
        notes: [
          {
            tone: 'neutral',
            text: 'サーバー名やデータベース名は、空のままなら Suite の既定が使われます。迷ったら空のままで大丈夫です。',
          },
        ],
      },
      {
        heading: '3. 同意 → 4. 確認 → 5. 完了',
        steps: [
          '免責事項（disclaimer）を読み、内容に同意するチェックを入れます。',
          '選んだアプリ・データベース・連携の内容を確認します。',
          '「完了」を押すと、設定ファイルの書き込みとデータベースの用意が行われます。',
        ],
        notes: [
          {
            tone: 'warning',
            text: 'インストールの完了は「技術的な準備ができた」という意味です。税務・会計・法令への適合を保証するものではありません。',
          },
        ],
      },
    ],
    related: ['concepts', 'safety'],
  },
]

/** Look up a guide by slug. */
export function getGuide(slug: string): Guide | undefined {
  return GUIDES.find((guide) => guide.slug === slug)
}
