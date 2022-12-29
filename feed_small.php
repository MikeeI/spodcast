<?php
    namespace Lukaswhite\FeedWriter;
    require "vendor/autoload.php";
    use Lukaswhite\FeedWriter\Itunes;

    $spotify_show_id = "4rOoJ6Egrf8K2IrywzwOMk";
    $limit = 10;
    $loop_limit = 1;

    $json_show = getShowInformation($spotify_show_id);
    $json_show_episodes = [];

    $episode_count = $json_show["total_episodes"];
    $loop_count = intdiv($episode_count, $limit) + 1;
    if ($loop_limit != null) {
        $loop_count = $loop_limit;
    }
    

    echo "Episode-Count: " . $episode_count . "\n";
    echo "Loop-Count: " . $loop_count . "\n";

    for ($i = 0; $i < $loop_count; $i++) {
        echo "Page: " . $i . "\n";

        $temp_episodes = getEpisodes($spotify_show_id, $limit, $i * $limit);
        for ($j = 0; $j < count($temp_episodes); $j++) {
            $json_show_episodes[] = $temp_episodes[$j];
        }
    }

    $feed = new Itunes();
    $channel = $feed->addChannel();
    $channel
        ->title($json_show["name"])
        ->subtitle($json_show["description"])
        ->description($json_show["description"])
        ->summary($json_show["description"])
        ->link("https://open.spotify.com/show/" . $spotify_show_id)
        ->image($json_show["images"][0]["url"])
        ->author($json_show["publisher"])
        ->owner($json_show["publisher"])
        ->explicit("no")
        ->copyright($json_show["publisher"])
        ->generator("iTunes")
        ->block("true")
        ->ttl(600);

    $channel->addCategory()->term("News");

    foreach ($json_show_episodes as $episode) {
        //echo $item["href"]."\n";
        //$temp_release_date = explode($json_show["release_date"],"-");
        //$release_date = $temp_release_date[2]."-".$temp_release_date[
        $channel
            ->addItem()
            ->title($episode["name"])
            ->author($json_show["publisher"])
            ->subtitle($json_show["description"])
            ->duration(sec2hms(substr_replace($episode["duration_ms"], "", -3)))
            ->summary($episode["description"], ENT_XML1, 'UTF-8')
            ->pubDate(new \DateTime($episode["release_date"]))
            ->guid("https://open.spotify.com/episode/" . $episode["id"])
            ->explicit("no")
            ->addEnclosure()
            ->url(
                "https://github.com/MossCation/space/releases/download/podcast/" .
                    $spotify_show_id .
                    "-" .
                    $episode["release_date"] .
                    "-" .
                    $episode["id"] .
                    ".m4a"
            )
            //->length( 8727310 )
            ->type("audio/x-m4a");
    }

    echo $feed->toString();
    file_put_contents("feed/4rOoJ6Egrf8K2IrywzwOMk_small_temp.rss", htmlspecialchars($feed->toString(), ENT_XML1, 'UTF-8'));

    function getEpisodes($spotify_show_id, $limit, $offset)
    {
        $BEARER = getenv("BEARER");

        $ch = curl_init(
            "https://api.spotify.com/v1/shows/" .
                $spotify_show_id .
                "/episodes?limit=" .
                $limit .
                "&market=es&offset=" .
                $offset
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer " . $BEARER,
        ]);

        $json = curl_exec($ch);
        $info = curl_getinfo($ch);

        $json_decoded = json_decode($json, true);
        $json_decoded_items = $json_decoded["items"];
        return $json_decoded_items;
    }

    function getShowInformation($spotify_show_id)
    {
        //Podcast Information
        $BEARER = getenv("BEARER");

        $ch = curl_init(
            "https://api.spotify.com/v1/shows/" . $spotify_show_id . "?market=es"
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer " . $BEARER,
        ]);

        $json = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $json_show = json_decode($json, true);

        return $json_show;
    }

    function sec2hms($sec, $padHours = false)
    {
        $hms = "";
        $hours = intval(intval($sec) / 3600);
        $hms .= $padHours
            ? str_pad($hours, 2, "0", STR_PAD_LEFT) . ":"
            : $hours . ":";
        $minutes = intval(($sec / 60) % 60);
        $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT) . ":";
        $seconds = intval($sec % 60);
        $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
        return $hms;
    }

?>
