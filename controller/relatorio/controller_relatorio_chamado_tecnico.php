<?php
require_once __DIR__ . '/../../database/conexaodb.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

class ControllerRelatorioChamadoTecnico {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getChamados($filtros = []) {
        try {
            $where = [];
            $params = [];

            $sql = "SELECT 
                    c.id,
                    c.numero_chamado,
                    c.tipo_prioridade,
                    c.descricao,
                    c.status,
                    c.data_abertura,
                    c.data_fechamento,
                    f.nome as filial_nome,
                    s.nome as setor_nome,
                    u.nome as nome_solicitante,
                    t.nome as nome_tecnico
                FROM chamados c
                JOIN filiais f ON c.filial_id = f.id
                JOIN setores s ON c.setor_id = s.id
                JOIN usuarios u ON c.usuario_abertura_id = u.id
                LEFT JOIN usuarios t ON c.tecnico_responsavel_id = t.id
                WHERE 1=1";

            // Se não houver filtros de data, mostrar apenas chamados do dia atual
            if (empty($filtros['data_inicio']) && empty($filtros['data_fim'])) {
                $where[] = "DATE(c.data_abertura) = CURDATE()";
            } else {
                // Se houver filtro de data início
                if (!empty($filtros['data_inicio'])) {
                    $where[] = "DATE(c.data_abertura) >= :data_inicio";
                    $params[':data_inicio'] = $filtros['data_inicio'];
                }
                // Se houver filtro de data fim
                if (!empty($filtros['data_fim'])) {
                    $where[] = "DATE(c.data_abertura) <= :data_fim";
                    $params[':data_fim'] = $filtros['data_fim'];
                }
            }

            if (!empty($filtros['status'])) {
                $where[] = "c.status = :status";
                $params[':status'] = $filtros['status'];
            }

            if (!empty($where)) {
                $sql .= " AND " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY 
                      CASE 
                        WHEN c.status = 'Aberto' AND c.tecnico_responsavel_id IS NULL THEN 1
                        ELSE 2
                      END,
                      c.data_abertura DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Erro ao buscar chamados: " . $e->getMessage());
            throw new Exception("Erro ao buscar dados dos chamados");
        }
    }

    public function exportarExcel($chamados) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos
        $sheet->setCellValue('A1', 'Número OS');
        $sheet->setCellValue('B1', 'Filial');
        $sheet->setCellValue('C1', 'Setor');
        $sheet->setCellValue('D1', 'Solicitante');
        $sheet->setCellValue('E1', 'Técnico');
        $sheet->setCellValue('F1', 'Prioridade');
        $sheet->setCellValue('G1', 'Status');
        $sheet->setCellValue('H1', 'Data Abertura');
        $sheet->setCellValue('I1', 'Data Fechamento');

        // Dados
        $row = 2;
        foreach ($chamados as $chamado) {
            $sheet->setCellValue('A'.$row, $chamado['numero_chamado']);
            $sheet->setCellValue('B'.$row, $chamado['filial_nome']);
            $sheet->setCellValue('C'.$row, $chamado['setor_nome']);
            $sheet->setCellValue('D'.$row, $chamado['nome_solicitante']);
            $sheet->setCellValue('E'.$row, $chamado['nome_tecnico'] ?? 'Não atribuído');
            $sheet->setCellValue('F'.$row, $chamado['tipo_prioridade']);
            $sheet->setCellValue('G'.$row, $chamado['status']);
            $sheet->setCellValue('H'.$row, date('d/m/Y H:i', strtotime($chamado['data_abertura'])));
            $sheet->setCellValue('I'.$row, $chamado['data_fechamento'] ? date('d/m/Y H:i', strtotime($chamado['data_fechamento'])) : '-');
            $row++;
        }

        // Auto-size colunas
        foreach(range('A','I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="relatorio_chamados.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
    }

    public function exportarPDF($chamados) {
        $html = '
        <style>
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; }
            h2 { color: #333; }
        </style>
        <h2>Relatório de Chamados</h2>
        <table>
            <thead>
                <tr>
                    <th>Número OS</th>
                    <th>Filial</th>
                    <th>Setor</th>
                    <th>Solicitante</th>
                    <th>Técnico</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Data Abertura</th>
                    <th>Data Fechamento</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($chamados as $chamado) {
            $html .= '<tr>';
            $html .= '<td>' . $chamado['numero_chamado'] . '</td>';
            $html .= '<td>' . $chamado['filial_nome'] . '</td>';
            $html .= '<td>' . $chamado['setor_nome'] . '</td>';
            $html .= '<td>' . $chamado['nome_solicitante'] . '</td>';
            $html .= '<td>' . ($chamado['nome_tecnico'] ?? 'Não atribuído') . '</td>';
            $html .= '<td>' . $chamado['tipo_prioridade'] . '</td>';
            $html .= '<td>' . $chamado['status'] . '</td>';
            $html .= '<td>' . date('d/m/Y H:i', strtotime($chamado['data_abertura'])) . '</td>';
            $html .= '<td>' . ($chamado['data_fechamento'] ? date('d/m/Y H:i', strtotime($chamado['data_fechamento'])) : '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("relatorio_chamados.pdf", ["Attachment" => true]);
    }
}
