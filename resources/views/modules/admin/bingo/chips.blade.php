@extends('layouts.app_rke')
@section('saldo')
    @include('layouts/saldo_usdt')
@endsection
<title>Fichas Bingo - PlayRKE</title>

@section('content')
    <div class="col-12">
        <div class="card bg-rke">
            <div class="card-header">
                <h4 class="my-n2">Fichas Bingo Mundi</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table id="t_1" class="table table-striped table-bordered  nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID/N°</th>
                                        <th>Nombre</th>
                                        <th>Imagen</th>
                                        <th>Opción</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($chips_b as $chips)
                                        <tr>
                                            <td>{{ $chips->id }}</td>
                                            <td>{{ $chips->nombre }}</td>
                                            <td>{{ $chips->imagen }}</td>
                                            <td>
                                                <a href="{{ route('bingo.chipsEdit', $chips->id) }}"
                                                    class="btn  btn-primary"><i class="fas fa-edit"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-4 bg-rke" style="margin: 0 auto;">
                <div class="mb-1" style="display:flex;">
                    <h4 class="col-12 " align="center">Bingo Mundi - 288 Fichas</h4>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
                <div class="mb-4" style="display: flex;">
                    <div class="col-12" style="display: flex; justify-content: center;">
                        <button>1</button>
                        <button>2</button>
                        <button>3</button>
                        <button>4</button>
                        <button>5</button>
                        <button>6</button>
                        <button>7</button>
                        <button>8</button>
                        <button>9</button>
                        <button>10</button>
                        <button>11</button>
                        <button>12</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
