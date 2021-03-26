<?php
class ewpt_helpers {
 
    
    /*
     * Block external leechers - for remote thumb creation
     * @return (bool) true if no leecher detected, otherwise dies with no-leech image
     */
    public static function block_external_leechers() {
        if(!EWPT_BLOCK_LEECHERS) {
            return true;
        }

        $my_host = preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST']);
        if(EWPT_BLOCK_LEECHERS && array_key_exists('HTTP_REFERER', $_SERVER) && (! preg_match('/^https?:\/\/(?:www\.)?' . $my_host  . '(?:$|\/)/i', $_SERVER['HTTP_REFERER']))){
            // base64 encoded "stop hotlinking" png image
            $imgData = base64_decode( "iVBORw0KGgoAAAANSUhEUgAAAGYAAABkCAMAAABDybVbAAADAFBMVEUAAACsl5dnJSTp3Nt+fn4A///Ov74fFhXAUE7OmJa5b23a0M/y7Oz///9UVFM1NTW+gX+wq6vOr66rW1onBQXlv76TPTv08vLt5OTTg4Ho0dHjras+GBfCXlujcnHAq6rBlpXMb20NBwfdmZgkJCQlJSUmJiYnJycoKCgpKSkqKiorKyssLCwtLS0uLi4vLy8wMDAxMTEyMjIzMzM0NDQ1NTU2NjY3Nzc4ODg5OTk6Ojo7Ozs8PDw9PT0+Pj4/Pz9AQEBBQUFCQkJDQ0NERERFRUVGRkZHR0dISEhJSUlKSkpLS0tMTExNTU1OTk5PT09QUFBRUVFSUlJTU1NUVFRVVVVWVlZXV1dYWFhZWVlaWlpbW1tcXFxdXV1eXl5fX19gYGBhYWFiYmJjY2NkZGRlZWVmZmZnZ2doaGhpaWlqampra2tsbGxtbW1ubm5vb29wcHBxcXFycnJzc3N0dHR1dXV2dnZ3d3d4eHh5eXl6enp7e3t8fHx9fX1+fn5/f3+AgICBgYGCgoKDg4OEhISFhYWGhoaHh4eIiIiJiYmKioqLi4uMjIyNjY2Ojo6Pj4+QkJCRkZGSkpKTk5OUlJSVlZWWlpaXl5eYmJiZmZmampqbm5ucnJydnZ2enp6fn5+goKChoaGioqKjo6OkpKSlpaWmpqanp6eoqKipqamqqqqrq6usrKytra2urq6vr6+wsLCxsbGysrKzs7O0tLS1tbW2tra3t7e4uLi5ubm6urq7u7u8vLy9vb2+vr6/v7/AwMDBwcHCwsLDw8PExMTFxcXGxsbHx8fIyMjJycnKysrLy8vMzMzNzc3Ozs7Pz8/Q0NDR0dHS0tLT09PU1NTV1dXW1tbX19fY2NjZ2dna2trb29vc3Nzd3d3e3t7f39/g4ODh4eHi4uLj4+Pk5OTl5eXm5ubn5+fo6Ojp6enq6urr6+vs7Ozt7e3u7u7v7+/w8PDx8fHy8vLz8/P09PT19fX29vb39/f4+Pj5+fn6+vr7+/v8/Pz9/f3+/v7///+qzvBvAAABAHRSTlP///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////8AU/cHJQAAAAlwSFlzAAALEQAACxEBf2RfkQAABZJJREFUaN69mu2aoyoMgHHoQ7WEaescF8R1qO39X+Me1LbLlwg65+SHbUflnYQQSAD9+V8EpT+KZdOWbxm6RsIPY0TT9WVIHp38KQw8hjIi/QPz3RjeRBkvEuzC8LYv06SHzRhoywzpxSaMeJSZMuB8TFdukAfPw+TZy7CczMF0fblVHumYR7lDgj4XwECkif70/V0Uxfepj+grUzBi4eXLRYDAUsqGECmxAF5fFljNOkYGX20Jp40iBNhLgBBKQVRfSRwX0wRe+qo5VVQwXwQlQC+BV7o4RvpvnChXhhauAMWiWuWgtX4R/EZZXDTotGI3FPcxBWoNMgoR0Ef9zcL4Q0DWLE0k97pILGD8ANNxliqCet4DQUwoWHYsXTB2DNeGMOHBn8MRol9yt7+Yhck4Sx++ZDa0Or/kcIDb+rTgYHAktOdwHMeWDiY2jWVxcNBsaDX4T3NvOofLUDBAgf7vXY8pqxx97HHKDYztIUBgD8d2g8bAWMoUVHDe7+kff85GIWX0f7SHw1vP2ZA3ZgiZHoVhO8dSZ4Anhlst8nma9Dltur89XCdArjc36j3SPE56dLPc54l5BJTZyTGbHMSMMVurjXlM8M0c6UQC5NhMmHOy2N4/pqPWE8ac9U7CWsMI3m7Ux7RaRUeM6c434kb2jRxhj1CN6R2bnc/seTmczwfefhy1fHwcf+v7+vr1un/Qn1R/nj9fr5DplatntQlj9Zb2M4TYfEGjnOE8fnz8Rv/o+/r6C6HrdP9TP1Lo7+g8Pjn+6cru0zsjxrTCaDRTvYGyv5gDOnI4ok9oEZoAbwyCF2ZqfsLQEXPWr7AzHjFmVzQUWevZi2RPJfT7Y2uMaHW4g7nf0YzRzRZPzP0+Yo4oGHA6ZWMaG8MmDPMwv9Bhwsz2mTAMHTXmPv7+nFhmOD5hZPnzGJzfRjujA5svCFUWRn9MGH6Y7k6YAk3aHN4Y0wc4skINN/tGzFrx8QdUumXtb09MOWNGPeGJ0f8QumL0dgEXY7oE5waGsSO6H9n8Az5f/jZhNIfPnnZ4YbSXXRncp24aZbAx5k9InuLHFUN8gDqYPg3D4OblC/8FhoGXlPEMzJCKYSKLM0RcgP0cx8F0rqfFODcvVUparYEzPMXa2qj2Ur+lJ0sbYwWb1XwWUjlWsJFO6Fyfg30OrM5rY+i0JgKRMC+mccwY1lzdaS1l/m1SOKYDS4Ls9ZPgmzgBI9jrTmfJUSUtkAR1OdjLds3SErgLqD5tHSa8EpKMLaCItxyUiam5jOtjDU6ipsVtuyHNpHF9Gn9x+6cq08OaUTqJcVprHfPMb6zEI7mkIZY51r9Qk1AaBakc4WX5TTiNKkJJYbI6uhS0wLH4J/lOcfstvaM5OMyxrIPVO2GvNuaYvj6dm0P14rpUfsjImQMcuzFcG8UUa3XUZ5RowPODzs6IoFgsDbU5paB4VUnWVqGr2lFCi21/8MLCwMII2NQ/hjLKKUI28Ygbtdsip8JurdPd5oIsuy3ssMz9b1VuebndbiLMIcQvEHtT1WMv54aLQFXdWxq1sIvz9TaZvUcA7vZIjzP6x6v9gkreWEkdqNyvlmO1uE0UGGlN0pKqj3S/jyGB59dBMrC9UOMIJswpu0gfQWjTT1Ou0Z1CEh4BQwcBnThvwkV/IouVfU/CvxaiYKtRrzxLf9GIpYMRlKjVXVzML7Hd4mGW2N64UElb334KkyMXUIk77Fhs38hXokg+L0Cg2gYZgBQ5hyzoJoXqkMGiZzkIJ7mQmyAq/wAMgSzQRchi0zkbvbEuU013A1yoraeGCBHiss44UZAF2XXUSlGg0dMwreKyLvaf6Cq08UQdPE1xqSmnpChW20g8Bqc0SjfY1JevSU5V01DgWCn106ftyFU32ggYRdb6e7w7LPkXOpgdqGeVOLEAAAAASUVORK5CYII="); 
            
            header('Content-Type: image/png');
            header('Content-Length: ' . sizeof($imgData));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header("Pragma: no-cache");
            header('Expires: ' . gmdate ('D, d M Y H:i:s', time()));

            die($imgData);
        }

        return true;	
    }

    

    /* Manage browser headers cache for remote URL thumbs
     * @param (string) $img_path
     */
    public static function manage_browser_cache($img_path) {
        $etag_file = md5($img_path);
        $etag_header = (isset($_SERVER['HTTP_IF_NONE_MATCH'])) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false;

        header("Etag: ". $etag_file);

        $seconds_to_cache = 3600 * 24 * 15; // 15 days
        $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
        header("Expires: ". $ts);
        header('Cache-Control: must-revalidate');
        header("Cache-Control: max-age=3600");
        header("Cache-Control: public");
        header('Pragma: public');
        
        // check if contents changed. If not, send 304 and exit
        if($etag_header == $etag_file) {
            header("HTTP/1.1 304 Not Modified");
            die();
        }
    }
}