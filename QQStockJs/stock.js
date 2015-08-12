console.info("aaaaaa");

function D(m, E) {
    var g = m.length,
        d = [];
    for (var l = 0; l < g; l++) {
        d.push(m[l] instanceof Array ? m[l].length : -1)
    }
    var U = [];
    for (var l = 0, f = Math.max.apply(null, d); l < f; l++) {
        var v = [];
        for (var p = 0; p < g; p++) {
            v.push(d[p] >= 0 ? m[p][l] : m[p])
        }
        U[l] = E.apply(U, v)
    }
    return U
};

function H (f, d) {
    return D([f, d], function(l, g) {
        return l + g
    })
};

function A (f, d) {
    return D([f, d], function(l, g) {
        return l - g
    })
};

function T (f, d) {
    return D([f, d], function(l, g) {
        return l * g
    })
};

function O (f, d) {
    return D([f, d], function(l, g) {
        return l / g
    })
};

function K (d) {
    return D([d], function(f) {
        return isNaN(f) ? 0 : f
    })
};

function R () {
    var f = Array.prototype.slice.apply(arguments),
        d = f.length;
    return D(f, function() {
        return Math.max.apply(Math, arguments)
    })
};

function M (d) {
    return D([d], function(f) {
        return Math.abs(f)
    })
};

function G (l, f) {
    var m = l.length,
        g = [];
    for (var d = 0; d < f; d++) {
        g.push(undefined)
    }
    return g.concat(l).slice(0, m)
};

function Q(f, d) {
    return D(f, d)
};

function z(m, f) {
    var o = [];
    if (m && m.length && f > 0) {
        var l = 0,
            d = 0,
            g = m.length;
        for (; d < g; d++) {
            m[d] && (l += m[d]), d >= f && m[d - f] && (l -= m[d - f]), d >= f - 1 && (o[d] = l)
        }
    }
    return o
};

// C.MA
function J(m, f) {
    var o = [];
    if (m && m.length && f > 0) {
        var l = 0,
            d = 0,
            g = m.length;
        for (; d < g; d++) {
            m[d] && (l += m[d]), d >= f && m[d - f] && (l -= m[d - f]), d >= f - 1 && (o[d] = l / f)
        }
    }
    return K(o)
};

function N(m, g, s) 
{
    var l = s / g,
        f = 1 - l,
        p = !1,
        d = D([m], function(o) {
            return p = p !== !1 && !isNaN(p) ? l * o + f * p : o
        });
    return d
};

function k(p, g) {
    var E = [];
    
    if (p && p.length && g > 0) {
        var m = 0,
            f = 0,
            l = p.length,
            v = 2 / (g + 1),
            d = 1 - v;
        for (; f < l; f++) {
            E[f] = f ? v * p[f] + d * E[f - 1] : p[f]
        }
    }
    return E
};

function S(l, f) {
    var m = [];
    for (var g = f - 1, d = l.length; g < d; g++) {
        m[g] = Math.max.apply(null, l.slice(g - f + 1, g + 1))
    }
    return m
};

function q(l, f) {
    var m = [];
    for (var g = f - 1, d = l.length; g < d; g++) {
        m[g] = Math.min.apply(null, l.slice(g - f + 1, g + 1))
    }
    return m
};

function B(p, g) {
    var E = [],
        m = J(p, g);
    for (var f = g - 1, l = p.length; f < l; f++) {
        var v = 0;
        for (var d = f - g + 1; d <= f; d++) {
            v += Math.pow(p[d] - m[f], 2)
        }
        E[f] = Math.sqrt(v / g)
    }
    return E
}

// function A(f, d) {
//     return D([f, d], function(l, g) {
//         return l - g
//     })
// };

// function T(f, d) {
//     return D([f, d], function(l, g) {
//         return l * g
//     })
// };

// function k(p,g)
// {
//     var E = [];
    
//     if (p && p.length && g > 0) {
//         var m = 0,
//             f = 0,
//             l = p.length,
//             v = 2 / (g + 1),
//             d = 1 - v;
//         for (; f < l; f++) {
//             E[f] = f ? v * p[f] + d * E[f - 1] : p[f]
//         }
//     }
//     return E 
// }

// function G (l, f) {
//     var m = l.length,
//         g = [];
//     for (var d = 0; d < f; d++) {
//         g.push(undefined)
//     }
//     return g.concat(l).slice(0, m)
// };

// C.MACD = L(function(m, f, u, l) {
function MACD(m, f, u, l)
{
    f = f || 26, u = u || 12, l = l || 9;
    var d = A(k(m, u), k(m, f)),
        g = k(d, l),
        p = T(A(d, g), 2);
    return {
        DIF: d,
        DEA: g,
        MACD: p
    }
};

function RSI(l, f) {
    var m = G(l, 1),
        g = A(l, m),
        d = M(g);
    return T(O(N(R(g, 0), f, 1), N(d, f, 1)), 100)
}

function KDJ(U, W, m, d, v, X) {
    var g = q(W, d),
        u = O(A(U, g), O(A(S(m, d), g), 100)),
        V = N(u, v, 1),
        E = N(V, X, 1),
        f = A(T(3, V), T(2, E));
    return [V, E, f]
}

function DMI(v, Y, f, d, p) {
    var aa = z(R(R(A(f, Y), M(A(f, G(v, 1)))), M(A(Y, G(v, 1)))), d),
        m = A(f, G(f, 1)),
        u = A(G(Y, 1), Y),
        V = z(Q([m, u], function(l, g) {
            return l > 0 && l > g ? l : 0
        }), d),
        U = z(Q([u, m], function(l, g) {
            return l > 0 && l > g ? l : 0
        }), d),
        X = O(T(V, 100), aa),
        Z = O(T(U, 100), aa),
        o = J(O(M(A(Z, X)), O(H(X, Z), 100)), p),
        W = O(H(o, G(o, p)), 2);
    return [X, Z, o, W]
}

function OBV(l, f) {
    var m = [0];
    for (var g = 1, d = l.length; g < d; g++) {
        l[g] > l[g - 1] ? m[g] = m[g - 1] + f[g] : l[g] < l[g - 1] ? m[g] = m[g - 1] - f[g] : m[g] = m[g - 1]
    }
    return O(m, 10000)
}

function WR(u, E, g, d, p) {
    p = p || 6;
    var U = S(E, d),
        f = O(T(A(U, u), 100), A(U, q(g, d))),
        m = S(E, p),
        v = O(T(A(m, u), 100), A(m, q(g, p)));
    return [f, v]
}

function BOLL(p, g, u) {
    g = g || 20, u = u || 2;
    var m = J(p, g),
        d = T(B(p, g), u),
        l = H(m, d),
        o = A(m, d);
    return [l, m, o]
}

function SAR(U, Y, m, d, v) {
    var Z = [],
        g = [],
        X = [],
        W = U.length,
        E = [],
        p = function(f) {
            if (f >= W) {
                return
            }
            Z[f] = Math.min.apply(null, Y.slice(f - m, f)), E[f] = 1;
            if (Z[f] > Y[f]) {
                V(f + 1)
            } else {
                X[f] = Math.max.apply(null, U.slice(f - m + 1, f + 1)), g[f] = d;
                while (f < W - 1) {
                    Z[f + 1] = Z[f] + g[f] * (X[f] - Z[f]) / 100, E[f + 1] = 1;
                    if (Z[f + 1] > Y[f + 1]) {
                        V(f + 2);
                        return
                    }
                    X[f + 1] = Math.max.apply(null, U.slice(f - m + 2, f + 2)), U[f + 1] > X[f] ? (g[f + 1] = g[f] + d, g[f + 1] > v && (g[f + 1] = v)) : g[f + 1] = g[f], f++
                }
            }
        },
        V = function(f) {
            if (f >= W) {
                return
            }
            Z[f] = Math.max.apply(null, U.slice(f - m, f)), E[f] = -1;
            if (Z[f] < U[f]) {
                p(f + 1);
                return
            }
            X[f] = Math.min.apply(null, Y.slice(f - m + 1, f + 1)), g[f] = d;
            while (f < W - 1) {
                Z[f + 1] = Z[f] + g[f] * (X[f] - Z[f]) / 100, E[f + 1] = -1;
                if (Z[f + 1] < U[f + 1]) {
                    p(f + 2);
                    return
                }
                X[f + 1] = Math.min.apply(null, Y.slice(f - m + 2, f + 2)), Y[f + 1] < X[f] ? (g[f + 1] = g[f] + d, g[f + 1] > v && (g[f + 1] = v)) : g[f + 1] = g[f], f++
            }
        };
    return U[m] > U[0] || Y[m] > Y[0] ? p(m) : V(m), [Z, E]
}

function get_ma(P)
{
    var R = [];
    var volume_data=[];
    var ma_data = [];
    for (var Q = 0, M = P.length; Q < M; Q++) {
        ma_data.push(parseFloat(P[Q][5]));
        volume_data.push(parseFloat(P[Q][2]));
    }
    var L = {
        ma_5:J(ma_data, 5),
        ma_10:J(ma_data, 10),
        ma_20:J(ma_data, 20),
        volume_ma_5:J(volume_data, 5),
        volume_ma_10:J(volume_data, 10),
        volume_ma_20:J(volume_data, 20),
    };
    console.info(L);
    return L;
}

function get_macd(N)
{
    var O = [];
    for (var M = 0, J = N.length; M < J; M++) {
        O[M] = parseFloat(N[M][2])
    }
    var L = MACD(O);
    return L;
    // for(var i=0;i<N.length;i++)
    // {
    //     var change = (i==0)?0:N[i][2]-N[i-1][2];
    //     var change_percent = (i==0)?0:100*change/N[i-1][2];
    //     change = round(change,2);
    //     change_percent = round(change_percent,2);
    //     var diff = round(L.DIF[i],3);
    //     var dea = round(L.DEA[i],3);
    //     var macd = round(L.MACD[i],3);
    //     var _info={
    //         time:N[i][0],
    //         opening_price:N[i][1],
    //         closing_price:N[i][2],
    //         max_price:N[i][3],
    //         min_price:N[i][4],
    //         volume:N[i][5],
    //         change:change,
    //         change_percent:change_percent,
    //         diff:diff,
    //         dea:dea,
    //         macd:macd,
    //     };
    //     history_data[i]=_info;
    // }
    //console.info(history_data);
}

function get_rsi(O)
{
    var Q = [];
    for (var N = 0, K = O.length; N < K; N++) {
        Q[N] = parseFloat(O[N][2])
    }
    var M = RSI(Q, 6),
        P = RSI(Q, 12),
        J = RSI(Q, 24);
    var rsi_data = {
        rsi_6:M,
        rsi_12:P,
        rsi_24:J,
    };
    console.info(rsi_data);
    return rsi_data;
}

function get_kdj(O)
{
    //console.info(stock_data);
    var Q = [],
        N = [],
        K = [];
    for (var M = 0, P = O.length; M < P; M++) {
        Q[M] = parseFloat(O[M][2]), K[M] = parseFloat(O[M][3]), N[M] = parseFloat(O[M][4])
    }
    var J = KDJ(Q, N, K, 9, 3, 3);
    console.info(J);
    return J;
}

function get_dmi(O)
{
    var Q = [],
    N = [],
    K = [];
    for (var M = 0, P = O.length; M < P; M++) {
        Q[M] = parseFloat(O[M][2]), K[M] = parseFloat(O[M][3]), N[M] = parseFloat(O[M][4])
    }
    var J = DMI(Q, N, K, 14, 6);
    return J;
}

function get_obv(N)
{
    var P = [],
        M = [];
    for (var J = 0, L = N.length; J < L; J++) {
        P[J] = parseFloat(N[J][2]), M[J] = parseInt(N[J][5], 10)
    }
    var O = OBV(P, M);
    //console.info(O);
    return O;
}

function get_wr(O)
{
    var Q = [],
        N = [],
        K = [];
    for (var M = 0, P = O.length; M < P; M++) {
        Q[M] = parseFloat(O[M][2]), K[M] = parseFloat(O[M][3]), N[M] = parseFloat(O[M][4])
    }
    var J = WR(Q, K, N, 10, 6);
    //console.info(J);
    return J;
}

function get_boll(O)
{
    var L = [],
        J = [],
        M = [],
        R = [];
    for (var K = 0, P = O.length; K < P; K++) {
        R[K] = parseFloat(O[K][1]), J[K] = parseFloat(O[K][3]), M[K] = parseFloat(O[K][4]), L[K] = parseFloat(O[K][2])
    }
    var N = BOLL(L, 20, 2);
    //console.info(N);
    return N;
}

function get_sar(O)
{
    var L = [],
        J = [],
        M = [],
        R = [];
    for (var K = 0, P = O.length; K < P; K++) {
        R[K] = parseFloat(O[K][1]), J[K] = parseFloat(O[K][3]), M[K] = parseFloat(O[K][4]), L[K] = parseFloat(O[K][2])
    }
    var N = SAR(J, M, 10, 2, 20);
    //console.info(N);
    return N;
}

function get_all(code,data)
{
    var len = data.length;
    console.info(data);
    var ma = get_ma(data);
    var macd = get_macd(data);
    // rsi 12 24的数据不一样
    var rsi = get_rsi(data);
    // j的数据不准
    var kdj = get_kdj(data);
    var dmi = get_dmi(data);
    var obv = get_obv(data);
    var wr = get_wr(data);
    var boll = get_boll(data);
    var sar = get_sar(data);
    var stock_data = [];

    for(var i=0;i<len;i++)
    {
        var change = (i==0)?0:data[i][2]-data[i-1][2];
        var change_percent = (i==0)?0:100*change/data[i-1][2];

        var _info={
            time:parseFloat(data[i][0]),
            opening_price:parseFloat(data[i][1]),
            closing_price:parseFloat(data[i][2]),
            max_price:parseFloat(data[i][3]),
            min_price:parseFloat(data[i][4]),
            volume:parseFloat(data[i][5]),
            change:change,
            change_percent:change_percent,
            diff:macd.DIF[i],
            dea:macd.DEA[i],
            macd:macd.MACD[i],
            ma_5:ma.ma_5[i],
            ma_10:ma.ma_10[i],
            ma_20:ma.ma_20[i],
            volume_ma_5:ma.volume_ma_5[i],
            volume_ma_10:ma.volume_ma_10[i],
            volume_ma_20:ma.volume_ma_20[i],
            rsi_6:rsi.rsi_6[i],
            rsi_12:rsi.rsi_12[i],
            rsi_24:rsi.rsi_24[i],
            k:kdj[0][i],
            d:kdj[1][i],
            j:kdj[2][i],
            di1:dmi[0][i],
            di2:dmi[1][i],
            adx:dmi[2][i],
            adxr:dmi[3][i],
            obv:obv[i],
            wr1:wr[0][i],
            wr2:wr[1][i],
            upper:boll[0][i],
            mid:boll[1][i],
            lower:boll[2][i],
            sar:sar[0][i],
        };
        stock_data[i]=_info;
    }
    save_data(code,stock_data);
    console.info(stock_data);
}

function save_data(code,stock_data)
{
    $.ajax({
        url: 'get_stock.php',
        type: 'POST',
        data:{
            type:'save_stock_data',
            code:code,
            stock_data:JSON.stringify(stock_data),
        },
        dataType: 'json',
        error: function(){
            console.info("save_data failed of " + code);
        },
        success: function(data){
            if(data.error_code!=0)
            {
                console.info("save_data failed of " + code);
            }
        }
    });   
}

function get_data(code,start_time,end_time,type)
{
    $.ajax({
        url: 'get_stock.php',
        type: 'POST',
        data:{
            type:'get_stock_data',
            code:code,
            start_time:start_time,
            end_time:end_time,
        },
        dataType: 'json',
        error: function(){
            console.info("获取关联关系数据失败！");
        },
        success: function(data){
            var Q=[];
            //console.info(data);
            switch(type)
            {
                case 'all':
                    get_all(code,data);
                    break;
                case 'ma':
                    get_ma(data);
                    break;
                case 'macd':
                    get_macd(data);
                    break;
                case 'rsi':
                    get_rsi(data);
                    break;
                case 'kdj':
                    get_kdj(data);
                    break;
                case 'dmi':
                    get_dmi(data);
                    break;
                case 'obv':
                    get_obv(data);
                    break;
                case 'wr':
                    get_wr(data);
                    break;
                case 'boll':
                    get_boll(data);
                    break;
                case 'sar':
                    get_sar(data);
                    break;
                default:
                    break;
            }
        }
    }); 
}



function get_all_stock()
{
    $.ajax({
        url: 'get_stock.php',
        type: 'POST',
        data:{
            type:'get_stock_list',
        },
        dataType: 'json',
        error: function(){
            console.info("get_all_stock failed of ");
        },
        success: function(data){
            console.info(data);
            stock_list = data;

            window.setInterval(task,2000); 
        }
    }); 
}

var stock_list;
var stock_index=0;
var start_time = '20140101';
var end_time = '20150810';
function task()
{
    var code = stock_list[stock_index];
    //code = 'sh600062';
    stock_index++;
    console.info("task stock_index is "+code);
    get_data(code,start_time,end_time,'all');
            // for(var i in data)
            // {
            //     var code = data[i];
            //     get_data(code,start_time,end_time,'all');
            //     break;
            // }
}

//ma数据
//var code = 'sh600062';
//var code = 'sh601299';
// var code = 'sh000001';
// var start_time = '20140101';
//  //var end_time = '20150504';
// //var start_time = '20141030';
// var end_time = '20150621';

//for(var i=0;i<)
get_all_stock();
//get_data(code,start_time,end_time,'all');

//macd数据
// var code = 'sh600062';
// var start_time = '20140901';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'macd');

//rsi数据
// var code = 'sh600062';
// var start_time = '20141028';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'rsi');

//kdj数据 
// var code = 'sh600062';
// var start_time = '20141118';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'kdj');
//dmi数据 
// var code = 'sh600062';
// var start_time = '20141111';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'dmi');

//obv数据 
// var code = 'sh600062';
// var start_time = '20141208';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'obv');

//W&R数据 
// var code = 'sh600062';
// var start_time = '20141111';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'wr');

//BOLL数据 
// var code = 'sh600062';
// var start_time = '20141028';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'boll');

//BOLL数据 
// var code = 'sh600062';
// var start_time = '20141028';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'boll');

//SAR数据 
// var code = 'sh600062';
// var start_time = '20141028';
// var end_time = '20150430';
// get_data(code,start_time,end_time,'sar');