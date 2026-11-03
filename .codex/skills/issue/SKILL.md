---
name: issue
description: Create a GitHub Issue for a requested change in this repository, clarifying unresolved product decisions and recording concrete acceptance criteria.
---

# GitHub Issue 作成

このリポジトリで依頼された機能追加や修正を、実装可能で確認しやすい GitHub Issue にまとめて登録する。

## 手順

1. Issue 作成前に `git remote -v` と `gh repo view` で対象リポジトリを確認する。既存 Issue や `.github` のテンプレートがあれば、書式と重複を確認する。
2. リポジトリの `AGENTS.md`、関連する設計資料、依頼文を確認する。依頼に書かれた受け入れ条件と、既存動作を維持する条件を区別する。
3. Issue の実装方針や期待動作を左右する未決事項が残っていれば、Issue を作る前に簡潔に質問する。依頼文から決められる内容は質問せず、依頼の範囲を広げない。
4. 短く具体的なタイトルを付け、本文に概要と受け入れ条件を記載する。受け入れ条件は個別に確認できるチェックリストにする。必要な場合のみ、実装上の制約や既存仕様を維持する条件を書く。依頼文にない仕様を補わない。
5. `gh issue create` で確認したリポジトリに作成する。GitHub 接続に失敗した場合は環境の承認手順に従って必要なネットワーク承認を求め、成功するまで作成済みと扱わない。
6. 作成後に返された URL を使って結果を伝える。作成に失敗した場合は、失敗したことと理由を伝える。

## 注意

- GitHub への Issue 登録は外部への書き込みである。ユーザーが Issue 作成を依頼した範囲でのみ実行し、関連する別 Issue、PR、コメントは作成しない。
- Issue 本文は依頼者の言語で書く。ユーザーが明示した条件や用語を保つ。
- 認可、入力制約、並び順などの現行仕様が依頼に関連するときは、対象リポジトリのガイドラインに従って受け入れ条件へ反映する。
