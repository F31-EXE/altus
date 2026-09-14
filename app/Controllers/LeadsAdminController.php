<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Models\Lead;

/**
 * Заявки с сайта в закрытой части.
 *
 * Создавать и редактировать заявки нельзя: их пишет только форма на сайте,
 * а править чужое обращение задним числом незачем. Отсюда можно прочитать,
 * отметить прочитанной и удалить.
 *
 * Удаление здесь не «прибраться в списке»: по закону о персональных данных
 * заявитель вправе потребовать удалить свои данные, и это должно делаться
 * без разработчика.
 */
final class LeadsAdminController extends Controller
{
    public function __construct()
    {
        Auth::require();
    }

    public function index(): void
    {
        $page = (int) Request::get('page', 1);

        $this->view('leads/index', [
            'leads' => Lead::paginateLatest($page, 30),
        ], 'Заявки с сайта');
        clear_old();
    }

    public function markRead(string $id): void
    {
        Csrf::check();

        $lead = Lead::find((int) $id);
        if (!$lead) {
            Flash::error('Заявка не найдена');
            $this->redirectAdmin('/leads');
        }

        Lead::update((int) $id, ['is_read' => $lead['is_read'] ? 0 : 1]);
        Flash::success($lead['is_read'] ? 'Отмечена как новая' : 'Отмечена прочитанной');
        $this->redirectAdmin('/leads');
    }

    public function destroy(string $id): void
    {
        Csrf::check();

        if (!Lead::find((int) $id)) {
            Flash::error('Заявка не найдена');
            $this->redirectAdmin('/leads');
        }

        Lead::delete((int) $id);
        Flash::success('Заявка удалена');
        $this->redirectAdmin('/leads');
    }
}
