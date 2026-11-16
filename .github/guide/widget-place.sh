php artisan tinker --execute='
$blueprint = Capell\Core\Models\Blueprint::firstOrCreate(["key" => "note", "type" => Capell\LayoutBuilder\Enums\LayoutTypeEnum::Widget->value], ["name" => "Note", "default" => true]);
$widget = Capell\LayoutBuilder\Models\Widget::firstOrCreate(["key" => "project-note"], ["name" => "Project note", "blueprint_id" => $blueprint->id, "status" => true]);
$widget->update(["component" => "project-note"]);
$layout = Capell\Core\Models\Layout::where("key", "studio")->first();
$containers = $layout->containers ?? [];
$placed = collect($containers["main"]["widgets"] ?? [])->contains(fn ($item) => ($item["widget_key"] ?? null) === "project-note");
if (! $placed) {
    $containers["main"]["widgets"][] = ["widget_key" => "project-note"];
    $layout->update(["containers" => $containers]);
}
'
php artisan cache:clear
